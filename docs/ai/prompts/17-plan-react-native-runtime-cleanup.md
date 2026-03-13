# Plan React Native Runtime Setup and Cleanup

Inspect the current project structure and provide a plan for preparing the React Native app for testing and development.

Do not modify any files yet.
Do not implement the plan yet.
Wait for my approval before making any edits.

## Background

A React Native app has been created in the project root `mobile/` directory.

I am seeing many errors and I believe there are missing Node modules or other setup issues required to run the React Native app alongside my Symfony 7 project.

I am also unsure whether anything needs to be added to Symfony `importmap` to support this React Native app.

Previously, I had created a React application in the project root `assets/` directory.
That older work includes files and a `vendor/` directory under `assets/`.
I no longer want to use that earlier React application.
I want to remove the previous React work under the project root `assets/` directory and use only the app in the project root `mobile/` directory.

## Goals

Please inspect the project and provide a step-by-step plan for the following:

1. Determine what is required to run and test the React Native app in the `mobile/` directory
2. Identify all missing or incorrect Node modules, package dependencies, configuration files, or scripts needed for the React Native app
3. Determine whether Symfony `importmap` is relevant to the React Native app, and explain why or why not
4. Identify all old React-related files in the project root `assets/` directory that are no longer needed
5. Propose a safe cleanup plan for removing the obsolete React work from `assets/`
6. Identify any Symfony-side configuration or API considerations needed for the React Native app to run correctly
7. Identify any risks, assumptions, or ambiguities before cleanup or setup begins

## Deliverables

Please provide:

1. A summary of the current state of:
    - the `mobile/` app
    - the old `assets/` React work
2. A list of missing setup items required to run the React Native app
3. A list of files and directories that appear safe to remove from `assets/`
4. A recommended order of operations for:
    - cleanup
    - dependency installation
    - configuration
    - test/run verification
5. The exact files you would modify, remove, or create if I approve the plan

## Constraints

- Do not modify existing Symfony web app behavior
- Do not modify Twig templates
- Do not remove anything yet
- Do not install packages yet
- Do not change `importmap` unless you determine it is truly relevant and explain why
- Keep the React Native app fully separate from the Symfony web UI
- Prefer the smallest and safest cleanup possible

Wait for my approval before making any edits.