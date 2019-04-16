<?php


/**
 *
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce_repeatorders
 * @sub-package: cms
 **/
class RepeatOrdersAdmin extends ModelAdminEcommerceBaseClass
{
    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $managed_models = 'RepeatOrder';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $url_segment = 'repeat-orders';

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_title = 'Repeat Orders';

    /**
     * standard SS variable.
     *
     * @var int
     */
    private static $menu_priority = 3.13;

    /**
     * Change this variable if you don't want the Import from CSV form to appear.
     * This variable can be a boolean or an array.
     * If array, you can list className you want the form to appear on. i.e. array('myClassOne','myClasstwo').
     */
    public $showImportForm = false;

    /**
     * standard SS variable.
     *
     * @var string
     */
    private static $menu_icon = 'ecommerce/images/icons/money-file.gif';


    public function urlSegmenter()
    {
        return $this->config()->get('url_segment');
    }


}
