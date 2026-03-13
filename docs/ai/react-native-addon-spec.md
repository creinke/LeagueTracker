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
League
└─ Seasons
└─ Sessions
└─ Events
└─ Games / TeamGames

# Entity Relationships (High Level)

Players are associated with the League.

---

# Controllers and Templates in Scope

## SecurityController

Endpoints:

- login
- logout

Templates:

- security/login.html.twig

---

## SeasonController

Endpoints:

- list
- view

Templates:

- season/list.html.twig
- season/view.html.twig

---

## EventController

Endpoints:

- list
- results
- view
- viewLastEvent
- viewNextEvent
- viewSeasonEvents

Templates:

- event/list.html.twig
- event/view.html.twig
- event/teammatchresults.html.twig

---

## GameController

Endpoints:

- postScores
- changePlayers
- results

Templates:

- game/change.players.html.twig
- game/view.html.twig
- game/postScores.html.twig

---

## TeamgameController

Endpoints:

- postScores
- view

Templates:

- teamgame/post.scores.html.twig
- teamgame/view.html.twig

---

## PlayerController

Endpoints:

- edit
- handicap
- list
- new
- view

Templates:

- player/edit.html.twig
- player/handicap.html.twig
- player/list.html.twig
- player/new.html.twig
- player/view.html.twig

---

# React Native App Responsibilities

The mobile app should:

- Authenticate the user
- Cache login context
- Display the same logical dialogs as the web app
- Mirror the structure of the Twig views
- Use API endpoints instead of HTML rendering

---

# Safety Constraints for AI Agents

AI agents must follow these rules:

1. Do not modify files unless explicitly instructed.
2. Always propose architecture before generating code.
3. Show file-level changes before editing.
4. Limit edits to the smallest possible scope.
5. Never alter Twig templates unless explicitly requested.
