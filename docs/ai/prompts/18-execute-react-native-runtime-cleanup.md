# Execute React Native Runtime Setup and Cleanup Plan

Implement the approved plan for preparing the React Native app in the project root `mobile/` directory for testing and development.

Work in review-first mode.

Do not make any edits until you first show me:
1. the exact files and directories you propose to modify, remove, or create
2. the exact package/dependency changes you propose
3. the exact diffs where applicable
4. any commands you propose to run

Wait for my approval before making changes.

## Scope

Execute only the approved setup and cleanup plan for:

- the React Native app in `mobile/`
- removal of the obsolete React work in the project root `assets/` directory, if confirmed safe
- any required React Native package/configuration setup
- any required API/base URL/environment configuration needed for local testing
- add Expo Go to the project root `mobile/` directory

## Goals

1. Prepare the `mobile/` app so it can be installed and run for testing
2. Resolve missing or incorrect Node module/package setup for the React Native app
3. Confirm whether Symfony `importmap` is irrelevant to this standalone React Native app, unless a specific project dependency proves otherwise
4. Remove only the obsolete React-related files from the project root `assets/` directory that are confirmed safe to delete
5. Keep the React Native app fully separate from the Symfony web UI
6. Preserve the existing Symfony web app behavior

## Constraints

- Do not modify Twig templates other than the ones obsoleted by the removal of obsolete React files
- Do not modify existing Symfony web controller behavior
- Do not remove anything outside the approved obsolete React files in `assets/`
- Do not make speculative cleanup changes
- Prefer the smallest and safest implementation
- If any deletion appears risky, stop and explain before proceeding
- If any package or configuration choice is uncertain, explain the options before proceeding

## Deliverables Before Editing

Before making any edits, provide:

1. A concise summary of the approved implementation approach
2. The exact files/directories to modify, create, or delete
3. The exact dependency/package changes proposed for `mobile/`
4. The exact commands you propose to run
5. The exact diffs, where applicable
6. Any assumptions or risks that still remain

## After Approval

After I approve, then:

1. Apply the approved cleanup and setup changes
2. Update only the necessary files
3. Remove only the approved obsolete files
4. Show a summary of all completed changes
5. List the exact commands I should run next to install dependencies and launch/test the React Native app

Wait for my approval before making any edits.