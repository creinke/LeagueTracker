# React Native Runtime Setup and Cleanup Plan

## Summary of Current State

### `mobile/` app
- **Source Code**: Contains a functional source structure in `src/` and `App.tsx`. Uses React Navigation, Axios, and AsyncStorage.
- **Missing Infrastructure**:
    - No `package.json` file.
    - No `node_modules` directory.
    - No native project files (`android/`, `ios/`).
    - No entry point configuration (e.g., `index.js`, `app.json`).
    - No `tsconfig.json` (though files are `.tsx`/`.ts`).

### Old `assets/` React work
- **Source Code**: Obsolete files at `assets/mobile/` and `assets/app.js`.
- **Dependencies**: Vendor files at `assets/vendor/` containing React 19 libraries.
- **Integration**: Referenced in `importmap.php` and potentially Twig templates (though instructions say not to modify Twig templates, the files themselves are obsolete).

## Missing Setup Items for React Native App

To make the `mobile/` app runnable, the following are required:
1. **`package.json`**: Needs to be created with at least the following dependencies:
    - `react`
    - `react-native`
    - `@react-navigation/native`
    - `@react-navigation/stack`
    - `react-native-screens`
    - `react-native-safe-area-context`
    - `react-native-gesture-handler`
    - `@react-native-async-storage/async-storage`
    - `axios`
2. **Entry Point**: A root `index.js` or `app.json` for React Native/Expo.
3. **Environment Config**: Update `mobile/src/api/client.ts` with a configurable `API_BASE_URL`.
4. **TypeScript Config**: A `tsconfig.json` file.
5. **Project Initialization**: Running `npm install` or `yarn install`.

## Obsolete Files to Remove from `assets/`

The following files and directories are confirmed safe to remove as they pertain only to the earlier React-in-Symfony attempt:
- `assets/mobile/` (directory)
- `assets/vendor/react/` (directory)
- `assets/vendor/react-dom/` (directory)
- `assets/vendor/scheduler/` (directory)
- `assets/vendor/installed.php` (if it only contains references to the above)
- `importmap.php` entries:
    - `react`
    - `react-dom`
    - `react-dom/client`
    - `scheduler`
    - `mobile/MobileAppInitializer`
    - `mobile/LeagueTrackerMobileApp`

## Recommended Order of Operations

1. **Cleanup**:
    - Remove identified obsolete files from `assets/`.
    - Update `importmap.php` to remove obsolete entries.
2. **Configuration (Mobile)**:
    - Create `mobile/package.json` with required dependencies.
    - Create `mobile/app.json` and `mobile/index.js`.
    - Create `mobile/tsconfig.json`.
3. **Dependency Installation**:
    - Run `npm install` within the `mobile/` directory.
4. **API Configuration**:
    - Update `mobile/src/api/client.ts` to use a more flexible base URL (e.g., via environment variable or local IP).
5. **Test/Run Verification**:
    - Attempt to start the development server (e.g., `npx react-native start` or `npx expo start`).

## Proposed Modifications

### Files to Modify
- `importmap.php`: Remove all React and mobile-related entries.
- `mobile/src/api/client.ts`: Adjust `API_BASE_URL` if necessary.

### Files to Remove
- `assets/mobile/LeagueTrackerMobileApp.js`
- `assets/mobile/MobileAppInitializer.js`
- `assets/vendor/react/*`
- `assets/vendor/react-dom/*`
- `assets/vendor/scheduler/*`
- `assets/vendor/installed.php`

### Files to Create
- `mobile/package.json`
- `mobile/app.json` (or `index.js`)
- `mobile/tsconfig.json`

## Symfony Importmap Relevance
**Conclusion**: `importmap.php` is **irrelevant** to the standalone React Native app.
**Reasoning**: `importmap.php` is used by the Symfony AssetMapper component to manage JavaScript modules for the web browser. React Native uses its own bundler (Metro) and dependency management (`package.json`), which is completely decoupled from the Symfony web asset pipeline.

## Risks and Assumptions
- **Risk**: Removal of `assets/vendor` might break other Symfony web UI components if they rely on these libraries (unlikely based on current analysis).
- **Assumption**: The `mobile/` app is intended to be a standard React Native or Expo project.
- **Assumption**: Symfony API endpoints are already functional and accessible by the mobile app.
- **Ambiguity**: Whether to use Expo or bare React Native. Expo is generally preferred for development simplicity unless specific native modules are required.
