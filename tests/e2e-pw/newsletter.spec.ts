import {test, expect, Page} from '@playwright/test';

test.describe( 'The Newsletter Plugin tests', () => {

  let page: Page;

  async function loginAsAdmin( page: Page ) {
    await page.goto('/wp-login.php?redirect_to=%2Fwp-admin%2F&reauth=1');
    await page.getByLabel('Username or Email Address').fill('admin');
    await page.locator('#user_pass').focus();
    await page.locator('#user_pass').fill('password');
    await page.getByLabel('Password', {exact: true}).press('Enter');
    await page.waitForLoadState( 'networkidle' );
  }

  async function addNewsletterSubscriber(page: Page, email: string, firstName: string, lastName: string) {
    await page.goto('/wp-admin/admin.php?page=newsletter_users_new', {waitUntil: 'domcontentloaded'});

    await page.getByPlaceholder('Valid email address').fill(email );
    await page.waitForTimeout(50);
    await page.getByRole('button', {name: '»'}).click();

    await page.locator('#options-name').fill(firstName);
    await page.locator('#options-surname').fill(lastName);

    await page.waitForTimeout(100);
    await page.getByRole('button', {name: ' Save'}).click();
  }

  async function createUser(page: Page, username: string, email: string ) {

    await page.goto('/wp-admin/user-new.php', {waitUntil: 'domcontentloaded'});

    await page.getByLabel('Username (required)').fill(username);
    await page.getByLabel('Email (required)').fill(email);

    await page.locator('#send_user_notification').uncheck();

    await page.getByRole('button', { name: 'Add New User' }).click();
    await page.waitForLoadState( 'domcontentloaded' ); // "New user created."

    return username;
  }

  async function logout(page: Page) {

    await page.goto('/wp-admin/', {waitUntil:'domcontentloaded'});

    let logoutLink = await page.evaluate(async() => {
      return document.getElementById('wp-admin-bar-logout').firstChild.getAttribute("href");
    });

    await page.goto(logoutLink, {waitUntil:'domcontentloaded'});

    await expect(page.locator('#login')).toContainText("logged out");
  }

  /**
   * Immediately manually trigger sending.
   *
   * Without intervention, it won't send correctly because of the environment.
   * After 900 seconds a "Run manually" button appears on `newsletter_system_scheduler` page.
   * `<input class="button-primary tnpc-button" type="submit" value="Run manually" onclick="this.form.act.value='trigger';return true;">`
   *
   * This function emulates that button click by filling and submitting the form immediately.
   *
   * `newsletter/system/scheduler.php:118`
   * @see NewsletterSystemAdmin::get_job_status()
   */
  async function manuallyTriggerNewsletterSend( page: Page ) {
    let newsletterSystemSchedulerUrl= "/wp-admin/admin.php?page=newsletter_system_scheduler";
    await page.goto(newsletterSystemSchedulerUrl, {waitUntil: 'domcontentloaded'});

    await page.evaluate(() => {
      const form = document.getElementById('tnp-body').querySelector('form');
      form.elements["act"].value='trigger';
      form.submit();
    });
  }

  async function getMostRecentEmailContent( page: Page, email: string, subject: string ) {

    // Check for sent email
    let mailLogUrl = "/wp-admin/admin.php?page=wpml_plugin_log";
    await page.goto(mailLogUrl, {waitUntil: 'domcontentloaded'});

    const row = page.locator('tr:has-text("' + email + '")').first();
    // await page.locator('#the-list').locator('.wp-mail-logging-action-column').first()
    await row.locator('.wp-mail-logging-action-column').first()
        .getByRole('button', { name: 'View log' }).click();

    await page.getByRole('link', { name: 'raw' }).click();
    await page.waitForTimeout(100);

    // TODO: Try another mail logging plugin to avoid flaky tests.
    await page.waitForLoadState( 'networkidle' );
    await page.waitForTimeout(100);

    const emailContent = await page.locator('.wp-mail-logging-modal-row-html-container').first();
    await emailContent.focus();
    var text = '';
    do {
      await page.waitForTimeout(100);
      text = await emailContent.textContent();
    } while (text === null || text.length == 0);
    return text
  }

  // This should be in a do-until loop checking that the newsletter has been sent.
  async function waitForNewsletterSendComplete(page: Page) {

    // # TODO: add a check for "newsletter has actually been sent"
    let times = 3;

    let newslettersListUrl = "/wp-admin/admin.php?page=newsletter_emails_index";
    do{
      await page.goto(newslettersListUrl, {waitUntil: 'domcontentloaded'});
      await manuallyTriggerNewsletterSend(page);
      times--;
    } while (times >= 0);
  }


  test.beforeAll(async ({ browser }) => {
    // Create page once and sign in.
    page = await browser.newPage();
    await loginAsAdmin(page);
  });

  test('test_logs_in_wpuser', async () => {
    let firstName = 'bob' + Math.random();
    let lastName = 'lastname';
    let email = firstName + '@example.com';

    await loginAsAdmin(page);

    await createUser( page, firstName, email );

    await addNewsletterSubscriber(page, email, firstName, lastName);

    let newNewsletterUrl = "/wp-admin/admin.php?page=newsletter_emails_composer";
    await page.goto(newNewsletterUrl, {waitUntil: 'domcontentloaded'});
    await page.waitForTimeout(200);

    // Page loads with template selection dialog open.
    await page.getByRole('img', {name: 'RAW'}).click();

    const defaultContent= await page.locator('#options-message' ).inputValue();
    const contentWithLink = defaultContent.replace(
        '<body>',
        '<body><a href="/">A link</a>'
    );

    const textarea= await page.locator('.CodeMirror' ).first().locator('textarea' ).first();
    await textarea.focus();
    await page.keyboard.press("Meta+A");
    await page.keyboard.press("Backspace");
    await textarea.focus();
    await page.waitForTimeout(100);
    await textarea.type(contentWithLink);
    await textarea.blur();

    await page.waitForTimeout(100);

    // New page loads: /wp-admin/admin.php?page=newsletter_emails_editorhtml&id=2
    await page.getByRole('button', {name: 'Next »'}).click();

    // Click "send now"
    page.once('dialog', dialog => {
      console.log(`Dialog message: ${dialog.message()}`);
      // Click OK.
      dialog.accept();
    });
    await page.getByRole('button', {name: 'Send now'}).click();

    await waitForNewsletterSendComplete(page);

    // This is flaky – returning an empty string. TODO: try a different mail logging plugin.
    let emailContent = await getMostRecentEmailContent(page, email, '');

    // A url contained in the newsletter body.
    let newsletterContentUrl = emailContent.match(/href="(.*?)"/)[1];

    await logout(page);

    await page.waitForTimeout(50);
    await page.goto(newsletterContentUrl, {waitUntil:'domcontentloaded'});

    const bodyLocator = page.locator("body")
    await expect(bodyLocator).toHaveClass(/\blogged-in\b/);

    // await page.goto('/wp-admin/profile.php', {waitUntil:'domcontentloaded'});
    // const woocommerceMyAccount = page.locator('.woocommerce-MyAccount-content')
    // if (await woocommerceMyAccount.isVisible()) {
    //   await expect(woocommerceMyAccount).toContainText('Hello ' + firstName);
    // } else {
    //   await expect(page.locator('#wp-admin-bar-my-account')).toContainText('Howdy, ' + firstName);
    // }
  });


  test('test_fills_in_woocommerce_checkout_without_wpuser', async () => {
    let firstName = 'bob' + Math.random();
    let lastName = 'lastname';
    let email = firstName + '@example.com';

    await loginAsAdmin(page);

    await addNewsletterSubscriber(page, email, firstName, lastName);

    let newNewsletterUrl = "/wp-admin/admin.php?page=newsletter_emails_composer";
    await page.goto(newNewsletterUrl, {waitUntil: 'domcontentloaded'});
    await page.waitForTimeout(200);

    // Page loads with template selection dialog open.
    await page.getByRole('img', {name: 'RAW'}).click();

    const defaultContent= await page.locator('#options-message' ).inputValue();
    const contentWithLink = defaultContent.replace(
        '<body>',
        '<body><a href="/">A link</a>'
    );

    const textarea= await page.locator('.CodeMirror' ).first().locator('textarea' ).first();
    await textarea.focus();
    await page.keyboard.press("Meta+A");
    await page.keyboard.press("Backspace");
    await textarea.focus();
    await textarea.type(contentWithLink);
    await textarea.blur();

    // New page loads: /wp-admin/admin.php?page=newsletter_emails_editorhtml&id=2
    await page.getByRole('button', {name: 'Next »'}).click();

    // Click "send now"
    page.once('dialog', dialog => {
      console.log(`Dialog message: ${dialog.message()}`);
      // Click OK.
      dialog.accept();
    });
    await page.getByRole('button', {name: 'Send now'}).click();

    await waitForNewsletterSendComplete(page);

    // This is flaky – returning an empty string. TODO: try a different mail logging plugin.
    let emailContent = await getMostRecentEmailContent(page, email, '');

    // A url contained in the newsletter body.
    let newsletterContentUrl = emailContent.match(/href="(.*?)"/)[1];

    await logout(page);
    
    await page.waitForTimeout(50);
    
    // Visit the newsletter URL.
    await page.goto(newsletterContentUrl, {waitUntil:'domcontentloaded'});

    // We do not expect to be logged in here because the point is to test the checkout process without being logged in.

    // Shop
    await page.goto('/shop', {waitUntil:'domcontentloaded'});

    await page.waitForTimeout(100);

    // Add item to cart.
    // await page.getByLabel('Add “Test Product” to your cart').click();
    await page.getByRole('button', {name: 'Add to cart'}).click();
    await page.waitForLoadState( 'networkidle' );
    await page.waitForTimeout(100);

    // Visit checkout.
    // await page.goto('/cart', {waitUntil:'domcontentloaded'});
    // await page.waitForLoadState( 'networkidle' );
    await page.goto('/blocks-checkout', {waitUntil:'domcontentloaded'});
    await page.waitForLoadState( 'networkidle' );

    await page.waitForTimeout(250);

    // Name and email should be filled out
    // if (await woocommerceMyAccount.isVisible()) {
    //   await expect(woocommerceMyAccount).toContainText('Hello ' + firstName);
    // } else {
      await expect(page.locator('#email')).toHaveValue(email);
    // }
  });

});