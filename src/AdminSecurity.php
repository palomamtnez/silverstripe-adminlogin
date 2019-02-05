<?php

namespace Axyr\SilverStripeAdminLogin;

use SilverStripe\Security\Security;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Controller;
use SilverStripe\Security\MemberAuthenticator\ChangePasswordForm;

/**
 * Class AdminSecurity.
 */
class AdminSecurity extends Security
{
    /**
     * @var array
     */
    private static $allowed_actions = [
        'passwordsent',
        'ChangePasswordForm',
    ];

    /**
     * Template thats used to render the pages.
     *
     * @config
     *
     * @var string
     */
    private static $template_main = 'AdminLogin';

    /**
     * @return void
     */
    public function init()
    {
        parent::init();

        $access = new IpAccess($this->getRequest()->getIP());
        if (!$access->hasAccess()) {
            $access->respondNoAccess($this);
        }

        if (Config::inst()->get('AdminLogin', 'UseTheme') !== true) {
            // this prevents loading frontend css and javscript files
            Injector::inst()->registerService(new AdminLoginPageController(), 'PageController');

            Requirements::css('axyr/silverstripe-adminlogin:client/css/style.css');
        }

        Injector::inst()->registerService(new AdminLoginForm(), 'MemberLoginForm');
    }

    /**
     * @param null $action
     *
     * @return string
     */
    public function Link($action = null)
    {
        return "AdminSecurity/$action";
    }

    /**
     * @return string
     */
    public static function isAdminLogin()
    {
        return strstr(Controller::curr()->getBackUrl(), '/admin/');
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        $request = Controller::curr()->getRequest();

        if ($url = $request->requestVar('BackURL')) {
            return $url;
        }

        return '';
    }

    /**
     * @param SS_HTTPRequest $request
     *
     * @return SS_HTTPResponse|HTMLText
     */
    public function passwordsent($request)
    {
        return parent::passwordsent($request);
    }

    /**
     * @see Security::getPasswordResetLink()
     * We overload this, so we can add the BackURL to the password resetlink
     *
     * @param Member $member
     * @param string $autologinToken
     *
     * @return string
     */
    public static function getPasswordResetLink($member, $autologinToken)
    {
        $autologinToken = urldecode($autologinToken);
        $selfControllerClass = __CLASS__;
        $selfController = new $selfControllerClass();

        return $selfController->Link('changepassword')."?m={$member->ID}&t=$autologinToken";
    }

    /**
     * @return ChangePasswordForm
     */
    public function ChangePasswordForm()
    {
        return new ChangePasswordForm($this, 'ChangePasswordForm');
    }
}
