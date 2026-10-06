import {request, FullConfig} from '@playwright/test';
import {RequestUtils} from '@wordpress/e2e-test-utils-playwright';

/**
 * Log in as the administrator once, over HTTP, and save the session cookies to the storage state
 * file every browser context is then started with – the same approach as WordPress core's own
 * `tests/e2e/config/global-setup.js`.
 */
async function globalSetup(config: FullConfig) {
    const {storageState, baseURL} = config.projects[0].use;
    const storageStatePath = typeof storageState === 'string' ? storageState : undefined;

    const requestContext = await request.newContext({baseURL});

    const requestUtils = new RequestUtils(requestContext, {storageStatePath, baseURL});

    // Authenticate and save the storageState to disk.
    await requestUtils.setupRest();

    await requestContext.dispose();
}

export default globalSetup;
