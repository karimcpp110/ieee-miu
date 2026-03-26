import admin from 'firebase-admin';
import fs from 'fs';

const SERVICE_ACCOUNT_PATH = './portal-beta/src/ieee-miu-portal-firebase-adminsdk-fbsvc-c1779cdfd7.json';
const serviceAccount = JSON.parse(fs.readFileSync(SERVICE_ACCOUNT_PATH, 'utf8'));

admin.initializeApp({
    credential: admin.credential.cert(serviceAccount)
});

const db = admin.firestore();

async function verify() {
    const collections = ['board_members', 'courses', 'events', 'forms', 'submissions', 'users'];
    for (const col of collections) {
        const snapshot = await db.collection(col).count().get();
        console.log(`Collection ${col}: ${snapshot.data().count} documents`);
    }
    process.exit(0);
}

verify().catch(err => {
    console.error(err);
    process.exit(1);
});
