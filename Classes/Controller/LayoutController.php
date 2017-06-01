<?php
namespace TYPO3\CMS\WireframeExample\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\CMS\Wireframe\Form\Data\GridContainerGroup;
use TYPO3\CMS\Wireframe\Form\Data\Group\ContentContainer;
use TYPO3\CMS\Wireframe\Form\Data\Group\ContentElement\Definitions;

/**
 * Controller for Web > Post module
 */
class LayoutController extends ActionController
{

    const CONTAINER_TABLE = 'tx_wireframe_example_domain_model_post';

    /**
     * @var TranslationConfigurationProvider
     */
    protected $translationConfigurationProvider;

    /**
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->translationConfigurationProvider = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
    }

    /**
     * Indexes all blog posts of a page
     *
     * @param int $page
     * @return void
     */
    public function indexAction($page)
    {
        if ($page > 0) {
            $this->createIndexActionButtons();

            $formResult = $this->createIndexActionFormResult();

            $this->view->assignMultiple([
                'title' => $formResult['title'],
                'form' => [
                    'content' => $formResult['html'],
                    'after' => $formResult['after'],
                    'action' => $formResult['returnUrl']
                ]
            ]);
        }
    }

    /**
     * Edits the content of a blog post
     *
     * @param int $page
     * @param int $post
     * @param int $language
     */
    public function editAction($page, $post, $language = 0)
    {
        if ($page > 0 && $post > 0) {
            $formResult = $this->createEditActionFormResult();

            $this->createEditActionMenus();
            $this->createEditActionSidebar();
            $this->createEditActionButtons();

            $this->view->assignMultiple([
                'title' => $formResult['title'],
                'form' => [
                    'before' => $formResult['before'],
                    'after' => $formResult['after'],
                    'content' => $formResult['html'],
                    'action' => $this->getHref('Layout', 'edit', [
                        'post' => $post,
                        'language' => $language
                    ])
                ]
            ]);
        }
    }

    /**
     * Generates the buttons for the index action
     *
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function createIndexActionButtons()
    {
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setHref(
                    GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class)->buildUriFromRoute(
                        'record_edit',
                        [
                            'edit' => [
                                self::CONTAINER_TABLE => [
                                    (int)$this->request->getArgument('page') => 'new'
                                ]
                            ],
                            'returnUrl' => $this->getHref(null, null, [])
                        ]
                    )
                )
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:wireframe_example/Resources/Private/Language/Classes/Controller/layout_controller:button.create'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-page-new', Icon::SIZE_SMALL))
        );
    }

    /**
     * Generates the record list for the index action
     *
     * @return array
     */
    protected function createIndexActionFormResult()
    {
        $list = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $list->script = BackendUtility::getModuleUrl('post_layout');
        $list->returnUrl = $this->getHref('Layout', 'index', ['page' => (int)$this->request->getArgument('page')]);
        $list->allFields = true;
        // @todo Disable the table link but keep enabled the sorting links
        //$list->disableSingleTableView = true;
        $list->hideTables = implode(',', array_diff(array_keys($GLOBALS['TCA']), [self::CONTAINER_TABLE]));
        $list->hideTranslations = '*';
        $list->deniedNewTables = array_diff(array_keys($GLOBALS['TCA']), [self::CONTAINER_TABLE]);
        $list->pageRow = BackendUtility::readPageAccess(
            (int)$this->request->getArgument('page'),
            $this->getBackendUserAuthentication()->getPagePermsClause(1)
        );
        //$list->counter++;
        $list->calcPerms = $this->getBackendUserAuthentication()->calcPerms($list->pageRow);
        $list->listOnlyInSingleTableMode = false;
        $list->clickTitleMode = 'edit';
        $list->dontShowClipControlPanels = true;

        $list->start(
            (int)$this->request->getArgument('page'),
            GeneralUtility::_GP('table') ? self::CONTAINER_TABLE : '',
            max((int)GeneralUtility::_GP('pointer'), 0),
            '',
            0,
            10
        );
        $list->setDispFields();
        $list->generateList();

        $this->view->getModuleTemplate()->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/AjaxDataHandler');

        return [
            'title' => $list->pageRow['title'],
            'html' => $list->HTMLcode,
            'after' => $list->eCounter > 0 ? $list->fieldSelectBox(self::CONTAINER_TABLE) : '',
            'returnUrl' => $list->returnUrl
        ];
    }

    /**
     * Generates the form result for the edit action
     *
     * @return array
     * @throws \TYPO3\CMS\Backend\Form\Exception
     */
    protected function createEditActionFormResult()
    {
        $formDataGroup = GeneralUtility::makeInstance(GridContainerGroup::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'tableName' => self::CONTAINER_TABLE,
            'vanillaUid' => (int)$this->request->getArgument('post'),
            'command' => 'edit',
            'returnUrl' => $this->getHref('Layout', 'edit', $this->request->getArguments()),
            'columnsToProcess' => ['content'],
            'customData' => [
                'tx_grid' => [
                    'columnToProcess' => 'content',
                    'containerProviderList' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['contentContainer'],
                    'itemProviderList' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['contentElement']
                ]
            ]
        ];

        $formData = array_merge(
            [
                'renderType' => 'layoutContainer',
                'renderData' => [
                    'languageUid' => (int)$this->request->getArgument('language')
                ]
            ],
            $formDataCompiler->compile($formDataCompilerInput)
        );

        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

        $formResult = $nodeFactory->create($formData)->render();

        $formResultCompiler->mergeResult($formResult);

        $formResultCompiler->addCssFiles();

        return array_merge(
            [
                'title' => $formData['recordTitle'],
                'after' => $formResultCompiler->printNeededJSFunctions()
            ],
            $formResult
        );
    }

    /**
     * Generates the sidebar for the edit action
     *
     * @return void
     */
    protected function createEditActionSidebar()
    {
        $formDataGroup = GeneralUtility::makeInstance(GridContainerGroup::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'tableName' => self::CONTAINER_TABLE,
            'vanillaUid' => (int)$this->request->getArgument('post'),
            'command' => 'edit',
            'returnUrl' => $this->getHref('Layout', 'edit', $this->request->getArguments()),
            'columnsToProcess' => ['content'],
            'customData' => [
                'tx_grid' => [
                    'columnToProcess' => 'content',
                    'containerProviderList' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['contentContainer'],
                    'itemProviderList' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['contentElement']
                ]
            ]
        ];
        $formData = array_merge(
            [
                'renderType' => 'contentPresetSidebarContainer'
            ],
            $formDataCompiler->compile($formDataCompilerInput)
        );

        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

        $formResult = $nodeFactory->create($formData)->render();

        $formResultCompiler->mergeResult($formResult);

        $formResultCompiler->addCssFiles();

        $this->view->getModuleTemplate()->getView()->setLayoutRootPaths(['EXT:wireframe/Resources/Private/Layouts']);
        $this->view->getModuleTemplate()->getView()->setTemplateRootPaths(['EXT:wireframe/Resources/Private/Templates']);

        $this->view->getModuleTemplate()->getView()->assign(
            'sidebar',
            array_merge(
                [
                    'after' => $formResultCompiler->printNeededJSFunctions()
                ],
                $formResult
            )
        );
    }

    /**
     * @return void
     * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
     */
    protected function createEditActionButtons()
    {
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setHref(GeneralUtility::sanitizeLocalUrl(
                    $this->getHref(
                        'Layout',
                        'index',
                        [
                            'page' => (int)$this->request->getArgument('page')
                        ]
                    )
                ))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.close'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-document-close', Icon::SIZE_SMALL))
        );
        $buttonBar->addButton(
            $buttonBar->makeLinkButton()
                ->setHref(
                    GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class)->buildUriFromRoute(
                        'record_edit',
                        [
                            'edit' => [
                                self::CONTAINER_TABLE => [
                                    (int)$this->request->getArgument('post') => 'edit'
                                ]
                            ],
                            'returnUrl' => $this->getHref(
                                'Layout',
                                'edit',
                                [
                                    'post' => (int)$this->request->getArgument('post'),
                                    'page' => (int)$this->request->getArgument('page'),
                                    'language' => (int)$this->request->getArgument('language')
                                ]
                            )
                        ]
                    )
                )
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:wireframe_example/Resources/Private/Language/Classes/Controller/layout_controller:button.edit'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-page-open', Icon::SIZE_SMALL)),
            ButtonBar::BUTTON_POSITION_LEFT,
            2
        );
    }

    /**
     * Generates the menus for the edit action
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function createEditActionMenus()
    {
        $request = $this->getControllerContext()->getRequest();

        $translationInfo = $this->translationConfigurationProvider->translationInfo('pages', (int)$this->request->getArgument('page'));
        $languages = $this->translationConfigurationProvider->getSystemLanguages((int)$this->request->getArgument('page'));

        uasort($languages, function ($a, $b) {
            return $a['title'] <=> $b['title'];
        });

        $languages = [$languages[0]] + array_intersect_key(
                $languages,
                $translationInfo['translations']
            );

        $menu = $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('languageMenu');

        foreach ($languages as $language) {
            $menu->addMenuItem(
                $menu->makeMenuItem()
                    ->setTitle($language['title'])
                    ->setHref($this->getHref('Layout', $request->getControllerActionName(), [
                        'page' => (int)$this->request->getArgument('page'),
                        'post' => (int)$this->request->getArgument('post'),
                        'language' => $language['uid']
                    ]))
                    ->setActive((int)$this->request->getArgument('language') === $language['uid'])
            );
        }

        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Initializes the arguments
     *
     * @return void
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException
     */
    protected function initializeAction()
    {
        $sessionData = $this->getBackendUserAuthentication()->getSessionData(self::class);

        if (!$this->request->hasArgument('language')) {
            $this->request->setArgument('language', (int)$sessionData['language']);
        } else {
            $sessionData['language'] = (int)$this->request->getArgument('language');
        }

        if (!$this->request->hasArgument('page')) {
            $this->request->setArgument('page', (int)GeneralUtility::_GP('id'));
        }

        $this->getBackendUserAuthentication()->setAndSaveSessionData(self::class, $sessionData);
    }

    /**
     * Returns the language service
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current backend user
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Creates the URI for a backend action
     *
     * @param string $controller
     * @param string $action
     * @param array $parameters
     * @return string
     */
    protected function getHref($controller, $action, $parameters = [])
    {
        return $this->objectManager->get(UriBuilder::class)
            ->setRequest($this->request)
            ->reset()
            ->uriFor($action, $parameters, $controller);
    }
}
