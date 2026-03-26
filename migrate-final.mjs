import admin from 'firebase-admin';
import fs from 'fs';
import path from 'path';

// --- CONFIGURATION ---
const SERVICE_ACCOUNT_PATH = './portal-beta/src/ieee-miu-portal-firebase-adminsdk-fbsvc-c1779cdfd7.json';
const SQL_FILE_PATH = 'c:/Users/ASUS/Downloads/if0_41134868_miu.sql';

// Initialize Firebase Admin
const serviceAccount = JSON.parse(fs.readFileSync(SERVICE_ACCOUNT_PATH, 'utf8'));
admin.initializeApp({
    credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

async function migrate() {
    console.log('--- Starting Final Migration ---');
    const sqlContent = fs.readFileSync(SQL_FILE_PATH, 'utf8');

    // Simple SQL Parser for INSERT statements
    const extractData = (tableName) => {
        const regex = new RegExp(`INSERT INTO \`${tableName}\` .*? VALUES\\s*(.*?);`, 'gs');
        const matches = sqlContent.matchAll(regex);
        const allRows = [];

        for (const match of matches) {
            const valuesBlock = match[1].trim();
            // Split by ),( while ignoring commas inside strings
            const rows = valuesBlock.split(/\),\s*\(/g).map(row => {
                return row.replace(/^\(/, '').replace(/\)$/, '').trim();
            });

            rows.forEach(row => {
                // Simple CSV-style split that handles quotes
                const parts = [];
                let current = '';
                let inQuotes = false;
                let quoteChar = '';

                for (let i = 0; i < row.length; i++) {
                    const char = row[i];
                    if ((char === "'" || char === '"') && row[i - 1] !== '\\') {
                        if (!inQuotes) {
                            inQuotes = true;
                            quoteChar = char;
                        } else if (char === quoteChar) {
                            inQuotes = false;
                        } else {
                            current += char;
                        }
                    } else if (char === ',' && !inQuotes) {
                        parts.push(current.trim());
                        current = '';
                    } else {
                        current += char;
                    }
                }
                parts.push(current.trim());

                // Cleanup parts (remove surrounding quotes and handle NULL)
                const cleanedParts = parts.map(p => {
                    if (p.toUpperCase() === 'NULL') return null;
                    if (p.startsWith("'") && p.endsWith("'")) return p.slice(1, -1).replace(/\\'/g, "'");
                    if (p.startsWith('"') && p.endsWith('"')) return p.slice(1, -1).replace(/\\"/g, '"');
                    return isNaN(p) ? p : Number(p);
                });

                allRows.push(cleanedParts);
            });
        }
        return allRows;
    };

    // 1. Migrate Site Settings (Global Document)
    console.log('Migrating Site Settings...');
    const settingsData = extractData('site_settings');
    const settingsObj = {};
    settingsData.forEach(row => {
        settingsObj[row[0]] = row[1];
    });
    await db.collection('settings').doc('site').set(settingsObj);

    // 2. Migrate Board Members
    console.log('Migrating Board Members...');
    const boardData = extractData('board_members');
    const boardBatch = db.batch();
    boardData.forEach(row => {
        const docRef = db.collection('board_members').doc(row[0].toString());
        boardBatch.set(docRef, {
            name: row[1],
            role: row[2],
            photo_url: row[3],
            committee: row[4],
            bio: row[5],
            linkedin_url: row[6],
            is_best: row[7] === 1
        });
    });
    await boardBatch.commit();

    // 3. Migrate Courses
    console.log('Migrating Courses...');
    const courseData = extractData('courses');
    for (const row of courseData) {
        await db.collection('courses').doc(row[0].toString()).set({
            title: row[1],
            description: row[2],
            instructor: row[3],
            duration: row[4],
            thumbnail: row[5],
            content: row[6] || '',
            file_path: row[7],
            created_at: row[8]
        });
    }

    // 4. Migrate Events
    console.log('Migrating Events...');
    const eventData = extractData('events');
    for (const row of eventData) {
        await db.collection('events').doc(row[0].toString()).set({
            title: row[1],
            category: row[2],
            description: row[3],
            event_date: row[4],
            image_path: row[5],
            created_at: row[6]
        });
    }

    // 5. Migrate Forms
    console.log('Migrating Forms...');
    const formData = extractData('forms');
    for (const row of formData) {
        let fields = [];
        try {
            fields = JSON.parse(row[3]);
        } catch (e) { console.error('Error parsing form JSON:', e); }
        await db.collection('forms').doc(row[0].toString()).set({
            title: row[1],
            description: row[2],
            fields: fields,
            created_at: row[4]
        });
    }

    // 6. Migrate Submissions
    console.log('Migrating Submissions...');
    const submissionData = extractData('submissions');
    const subBatch = db.batch();
    submissionData.forEach(row => {
        let data = {};
        try {
            data = JSON.parse(row[2]);
        } catch (e) { }
        const docRef = db.collection('submissions').doc(row[0].toString());
        subBatch.set(docRef, {
            form_id: row[1].toString(),
            data: data,
            submitted_at: row[3],
            status: row[4],
            admin_notes: row[5],
            student_id: row[6] ? row[6].toString() : null
        });
    });
    await subBatch.commit();

    // 7. Migrate Students as Users
    console.log('Migrating Students...');
    const studentData = extractData('students');
    for (const row of studentData) {
        await db.collection('users').doc(`student_${row[0]}`).set({
            uid: `student_${row[0]}`,
            full_name: row[1],
            email: row[2],
            role: 'student',
            student_id: row[4],
            created_at: row[5]
        }, { merge: true });
    }

    // 8. Migrate Gallery Sections (Albums)
    console.log('Migrating Gallery Sections...');
    const gallerySectionData = extractData('gallery_sections');
    for (const row of gallerySectionData) {
        await db.collection('gallery_sections').doc(row[0].toString()).set({
            title: row[1],
            description: row[2],
            created_at: row[3]
        });
    }

    // 9. Migrate Standalone Gallery Photos
    console.log('Migrating Gallery Photos...');
    const galleryPhotoData = extractData('gallery_photos');
    for (const row of galleryPhotoData) {
        await db.collection('gallery_photos').doc(row[0].toString()).set({
            section_id: row[1].toString(),
            image_path: row[2],
            created_at: row[3]
        });
    }

    // 10. Migrate Event Gallery (Photos attached to events)
    console.log('Migrating Event Gallery...');
    const eventGalleryData = extractData('event_gallery');
    for (const row of eventGalleryData) {
        await db.collection('events').doc(row[1].toString()).collection('gallery').doc(row[0].toString()).set({
            image_path: row[2],
            created_at: row[3]
        });
    }

    // 11. Migrate Badges
    console.log('Migrating Badges...');
    const badgeData = extractData('badges');
    for (const row of badgeData) {
        await db.collection('badges').doc(row[0].toString()).set({
            name: row[1],
            description: row[2],
            requirements_type: row[3],
            requirements_value: row[4]
        });
    }

    // 12. Migrate User Stats & Progress
    console.log('Migrating User Activity/Stats...');
    const userStatsData = extractData('user_stats');
    for (const row of userStatsData) {
        await db.collection('users').doc(`student_${row[0]}`).update({
            courses_completed: row[1],
            events_attended: row[2],
            last_login: row[3]
        }).catch(() => { }); // Skip if user doesn't exist
    }

    console.log('--- Migration Successfully Completed! ---');
}

migrate().catch(console.error);
