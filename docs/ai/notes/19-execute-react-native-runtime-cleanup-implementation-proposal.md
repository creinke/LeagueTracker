# React Native Runtime Setup and Cleanup Implementation Proposal

## 1. Concise Summary of the Approved Implementation Approach

The implementation will focus on two main areas:
1. **Obsolete React Cleanup**: Removing the earlier, failed attempt to integrate React directly into the Symfony asset pipeline via AssetMapper/Importmap. This involves deleting files in `assets/mobile/`, `assets/vendor/`, and updating `importmap.php`.
2. **Standalone Mobile App (Expo Go)**: Preparing the `mobile/` directory to function as a standalone React Native project using Expo Go. This involves creating a `package.json` with the necessary dependencies based on the existing source code (`mobile/src/`), adding Expo configuration (`app.json`), and setting up a clear entry point.

## 2. Exact Files/Directories to Modify, Create, or Delete

### To Delete
- `assets/mobile/LeagueTrackerMobileApp.js`
- `assets/mobile/MobileAppInitializer.js`
- `assets/vendor/react/` (and all subfiles)
- `assets/vendor/react-dom/` (and all subfiles)
- `assets/vendor/scheduler/` (and all subfiles)
- `assets/vendor/installed.php`

### To Modify
- `importmap.php`: Remove all React and mobile-related entries.
- `mobile/src/api/client.ts`: Update `API_BASE_URL` to be more flexible for local testing.

### To Create
- `mobile/package.json`: Project manifest and dependencies.
- `mobile/app.json`: Expo configuration.
- `mobile/tsconfig.json`: TypeScript configuration.
- `mobile/index.js` (or `AppEntry.js` if necessary for Expo): Application entry point.

## 3. Exact Dependency/Package Changes Proposed for `mobile/`

The `mobile/package.json` will be created with the following dependencies (matched to existing source code imports):

```json
{
  "name": "leaguetracker-mobile",
  "version": "1.0.0",
  "main": "node_modules/expo/AppEntry.js",
  "scripts": {
    "start": "expo start",
    "android": "expo start --android",
    "ios": "expo start --ios",
    "web": "expo start --web"
  },
  "dependencies": {
    "expo": "~52.0.0",
    "expo-status-bar": "~2.0.0",
    "react": "18.3.1",
    "react-native": "0.76.0",
    "@react-navigation/native": "^6.0.0",
    "@react-navigation/stack": "^6.0.0",
    "react-native-screens": "~4.0.0",
    "react-native-safe-area-context": "4.12.0",
    "react-native-gesture-handler": "~2.20.2",
    "@react-native-async-storage/async-storage": "1.23.1",
    "axios": "^1.7.0"
  },
  "devDependencies": {
    "@babel/core": "^7.25.2",
    "@types/react": "~18.3.12",
    "typescript": "^5.3.3"
  },
  "private": true
}
```
*Note: Versions are based on current stable Expo SDK 52 compatibility.*

## 4. Exact Commands Proposed to Run

**Note: These will be run in the `mobile/` directory.**

```powershell
cd mobile
# After the package.json is created:
npm install
# To start the app later (after installation):
npx expo start
```

## 5. Exact Diffs where Applicable

### `importmap.php`
```diff
--- a/importmap.php
+++ b/importmap.php
@@ -16,23 +16,4 @@
         'path' => './assets/app.js',
         'entrypoint' => true,
     ],
-    'react' => [
-        'version' => '19.2.3',
-    ],
-    'react-dom' => [
-        'version' => '19.2.3',
-    ],
-    'react-dom/client' => [
-        'version' => '19.2.3',
-    ],
-    'scheduler' => [
-        'version' => '0.27.0',
-    ],
-    'mobile/MobileAppInitializer' => [
-        'path' => 'mobile/MobileAppInitializer.js',
-        'entrypoint' => true,
-    ],
-    'mobile/LeagueTrackerMobileApp' => [
-        'path' => 'mobile/LeagueTrackerMobileApp.js',
-    ],
 ];
```

### `mobile/src/api/client.ts`
```diff
--- a/mobile/src/api/client.ts
+++ b/mobile/src/api/client.ts
@@ -1,7 +1,9 @@
 import axios from 'axios';
 import AsyncStorage from '@react-native-async-storage/async-storage';
+import Constants from 'expo-constants';
 
-const API_BASE_URL = 'https://leaguetracker7.local/api'; // Adjust to your actual development URL
+// Get local development IP from Expo Constants or fallback to local domain
+const API_BASE_URL = Constants.expoConfig?.extra?.apiUrl || 'https://leaguetracker7.local/api';
 
 const client = axios.create({
   baseURL: API_BASE_URL,
```

## 6. Any Assumptions or Risks that still Remain

- **Assumption**: The existing source code in `mobile/src/` is mostly functional and its dependencies can be correctly inferred from imports.
- **Assumption**: Using Expo Go (SDK 52) is preferred for ease of local testing.
- **Risk**: Some native modules might require specialized configuration beyond a simple `npm install` if they are not part of the standard Expo Go set (though `async-storage` and `react-navigation` are standard).
- **Risk**: Deleting `assets/vendor/installed.php` is safe because it only contains metadata for the React packages being removed.
- **Ambiguity**: The exact local IP of the Symfony server. The updated `client.ts` will attempt to use an environment/constant approach to allow easier switching.
