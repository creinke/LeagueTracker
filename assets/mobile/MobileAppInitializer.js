import React from 'react';
import { createRoot } from 'react-dom/client';
import LeagueTrackerMobileApp from 'mobile/LeagueTrackerMobileApp';

const container = document.getElementById('mobile-root');
// ... rest of code
if (container) {
    const root = createRoot(container);
    root.render(React.createElement(LeagueTrackerMobileApp));
}
