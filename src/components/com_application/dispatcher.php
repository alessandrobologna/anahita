<?php

/**
 * Application Dispatcher.
 *
 * @category   Anahita
 *
 * @author     Rastin Mehr <rastin@anahitapolis.com>
 * @copyright  2008 - 2016 rmdStudio Inc.
 * @license    GNU GPLv3 <http://www.gnu.org/licenses/gpl-3.0.html>
 *
 * @link       http://www.GetAnahita.com
 */
class ComApplicationDispatcher extends LibApplicationDispatcher
{
    /**
     * Constructor.
     *
     * @param KConfig $config An optional KConfig object with configuration options.
     */
    public function __construct(KConfig $config)
    {
        parent::__construct($config);

        //parse route
        $this->registerCallback('before.run',  array($this, 'load'));
    }

    /**
     * Run the application dispatcher.
     *
     * @param KCommandContext $context Command chain context
     *
     * @return bool
     */
    protected function _actionRun(KCommandContext $context)
    {
        $this->_application->_initialize($context);

        dispatch_plugin('system.onAfterDispatch');

        $this->route();
    }

    /**
     * Dispatches the component.
     *
     * @param KCommandContext $context Command chain context
     *
     * @return bool
     */
    protected function _actionDispatch(KCommandContext $context)
    {
        if ($context->request->get('option') !== 'com_application') {
            parent::_actionDispatch($context);
        }

        dispatch_plugin('system.onAfterDispatch', array( $context ));

        //render if it's only an HTML
        //otherwise just send back the request
        //@TODO rastin. For some reason the line below Need to fix the line below
        //not working properly
        //$redirect = $context->response->isRedirect()

        $location = $context->response->getHeader('Location');
        $isHtml = $context->request->getFormat() == 'html';
        $isAjax = $context->request->isAjax();

        if (!$location && $isHtml && !$isAjax) {

            $config = array(
                'request' => $context->request,
                'response' => $context->response,
                'theme' => $this->_application->getTemplate(),
            );

            $layout = $this->_request->get('tmpl', 'default');

            $this->getService('com:application.controller.page', $config)
                 ->layout($layout)
                 ->render();
        }

        dispatch_plugin('system.onAfterRender', array( $context ));

        $this->send($context);
    }

    /**
     * Routers.
     *
     * @param KCommandContext $context Dispatcher context
     */
    protected function _actionRoute(KCommandContext $context)
    {
        parent::_actionRoute($context);

        $component = $context->request->get('option');

        if (empty($component)) {
            $context->request->set('option', 'com_application');
        }

        $this->dispatch();
    }

    /**
     * Callback to handle Exception.
     *
     * @param KCommandContext $context Command chain context
     *                                 caller => KObject, data => mixed
     *
     * @return KException
     */
    protected function _actionException($context)
    {
        $exception = $context->data;

        //if KException then conver it to KException
        if ($exception instanceof KException) {
            $exception = new RuntimeException($exception->getMessage(), $exception->getCode());
        }

        //if cli just print the error and exit
        if (PHP_SAPI == 'cli') {
            print "\n";
            print $exception."\n";
            print debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            exit(0);
        }

        $code = $exception->getCode();

        //check if the error is code is valid
        if ($code < 400 || $code >= 600) {
            $code = KHttpResponse::INTERNAL_SERVER_ERROR;
        }

        $context->response->status = $code;

        $config = array(
            'response' => $context->response,
            'request' => $context->request,
            'theme' => 'shiraz'
        );

        //if ajax or the format is not html
        //then return the exception in json format
        if ($context->request->isAjax() || $context->request->getFormat() != 'html') {
            $context->request->setFormat('json');
        }

        $this->getService('com:application.controller.exception', $config)
        ->layout('error')
        ->render($exception);

        $this->send($context);
    }
}