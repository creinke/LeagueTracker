# React Native Add-On Specification

## Goal

Build a **React Native mobile add-on** for the existing Symfony 7 application.

The mobile application must **reuse existing application logic and data** while **leaving the existing web application completely unchanged**.

The mobile app will consume **new Symfony API endpoints** that expose the data currently rendered by Twig templates.

---

# Non-Goals

The following must NOT be changed:

- Existing Symfony web routes
- Existing Twig templates
- Existing database schema
- Existing controller logic unless absolutely necessary
- ROLE_ADMIN and ROLE_SAMPLE dialogs

The mobile app is **additive only**.

---

# Scope

This specification covers **ROLE_USER dialogs only**.

Ignore all:

- ROLE_ADMIN dialogs
- ROLE_SAMPLE dialogs
- administrative workflows

---

# Application Context

After login, the **logged-in user determines the active league**.

The league determines:

- accessible seasons
- associated events
- players
- teams
- results

This league context should be **cached in the mobile application after login**.

---

# Mobile Menu Behavior

## Before Login

Menu items:

- Home
- Login
- Help

## After Login

Menu items:

- Home
- Logout
- Players
- Seasons
- Events
- Help

---

# Entity Relationships (High Level)
