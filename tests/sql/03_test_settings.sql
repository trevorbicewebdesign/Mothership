SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

--
-- Test-only settings applied on top of the base Joomla schema.
--
-- "System - Joomla! Statistics" shows a "Help us make Joomla! better" message on
-- every admin page load until it is answered. It renders that message into
-- #system-message-container asynchronously, which wipes messages the page just
-- enqueued and shifts the layout under Selenium clicks. The tests used to click
-- "Hide Forever" after each login, but the AJAX call that persists that choice was
-- often aborted by the next navigation, so the popup came back at random. Nothing
-- under test needs the plugin, so it is off.
--

UPDATE `jos_extensions` SET `enabled` = 0 WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'stats';

--
-- "System - Guided Tours" auto-starts the welcome tour the first time a user logs
-- in. If the user is not already on the tour's start page it redirects the browser
-- there, and the test database is reloaded before every test, so this fired on the
-- first page of every admin test and pulled the browser back to the dashboard
-- mid-assertion (stale element / missing toolbar errors).
--

UPDATE `jos_extensions` SET `enabled` = 0 WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'guidedtours';
UPDATE `jos_guidedtours` SET `autostart` = 0;
