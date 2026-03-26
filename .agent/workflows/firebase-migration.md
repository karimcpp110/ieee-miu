---
description: How to migrate IEEE MIU from InfinityFree (PHP/MySQL) to Firebase
---

# IEEE MIU Firebase Migration Workflow

This workflow outlines the step-by-step process to migrate the IEEE MIU platform from a traditional PHP/MySQL stack to a serverless Firebase architecture.

## 1. Project Initialization
1. Create a new Firebase project at [Firebase Console](https://console.firebase.google.com).
2. Register a "Web App" and obtain the `firebaseConfig` object.
3. Install Firebase CLI: `npm install -g firebase-tools`.
4. Initialize the project: `firebase init`. Select Hosting, Firestore, Storage, and (optional) Functions.

## 2. Authentication Migration
1. Enable Email/Password authentication in the Firebase Console.
2. In the app, replace PHP session-based login (`Auth.php`, `login.php`) with Firebase Auth SDK calls.
3. Users can be imported using the Admin SDK if existing passwords are hash-compatible, or prompted to reset passwords on their first login.

## 3. Database Migration (MySQL to Firestore)
1. Map MySQL tables to Firestore collections:
   - `users` -> `users` collection
   - `courses` -> `courses` collection
   - `events` -> `events` collection
2. Use a migration script to read existing data from MySQL and write to Firestore.
3. Use sub-collections for nested patterns (e.g., `courses/{courseId}/materials`).

## 4. Storage Migration (Uploads to Firebase Storage)
1. Configure Firebase Storage rules for public/private access.
2. Replace local PHP file handling (`uploads/`) with the Firebase Storage SDK.
3. Move existing files from `uploads/` to Firebase Storage buckets using a script or manually.

## 5. Logic Migration (PHP to Cloud Functions/Client-side)
1. Port static UI components to a modern frontend (React/Vite recommended or keep as static HTML).
2. Move complex business logic (e.g., certificate generation) to Firebase Cloud Functions or client-side libraries.
3. Use Firestore security rules to replace PHP-side access control (RBAC).

## 6. Hosting and Deployment
1. Configure `firebase.json` for hosting redirects and rewrites.
2. Deploy the site: `firebase deploy`.
3. Add a custom domain if necessary (free through `ieeemiu-portal.web.app`).
