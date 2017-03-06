<?php
/**
 * @author         Pierre-Henry Soria <ph7software@gmail.com>
 * @copyright      (c) 2012-2017, Pierre-Henry Soria. All Rights Reserved.
 * @license        GNU General Public License; See PH7.LICENSE.txt and PH7.COPYRIGHT.txt in the root directory.
 * @package        PH7 / App / System / Module / Game / Controller
 */
namespace PH7;

use PH7\Framework\Navigation\Page;
use PH7\Framework\Mvc\Model\Statistic as Stat;
use PH7\Framework\Mvc\Router\Uri;

class MainController extends Controller
{
    const GAMES_PER_PAGE = 10, CATEGORIES_PER_PAGE = 10;

    /**
     * @internal Protected access because AdminController derived class uses these attributes
     *
     * @var object $oGameModel
     * @var object $sTitle
     * @var string $$sMetaKeywords
     * @var integer $iTotalGames
     */
    protected $oGameModel, $oPage, $sTitle, $sMetaKeywords, $iTotalGames;

    public function __construct()
    {
        parent::__construct();
        $this->oGameModel = new GameModel;
        $this->oPage = new Page;

        $this->sMetaKeywords = t('game,free,flash,game site,flash game,games,gaming,online game'); // Predefined meta_keywords tags
        $this->view->meta_keywords = $this->sMetaKeywords;
    }

    public function index()
    {
        $this->view->total_pages = $this->oPage->getTotalPages($this->oGameModel->totalGames(), self::GAMES_PER_PAGE);
        $this->view->current_page = $this->oPage->getCurrentPage();
        $oGames = $this->oGameModel->get(null, null, $this->oPage->getFirstItem(), $this->oPage->getNbItemsByPage());

        $this->setMenuVars();

        if (empty($oGames))
        {
            $this->sTitle = t('No Games Found!');
            $this->_notFound();
        }
        else
        {
            $this->view->page_title = t('Games Zone - Free Games');
            $this->view->h1_title = t('Games Zone Party');
            $this->view->meta_description = t('Free Games for Gamers, Flash Games, Free Online Games');
            $this->view->h2_title = $this->sTitle;

            $this->view->games = $oGames;
        }

        $this->output();
    }

    public function game()
    {
        $oGame = $this->oGameModel->get(strstr($this->httpRequest->get('title'), '-', true), $this->httpRequest->get('id'), 0, 1);

        if (empty($oGame))
        {
            $this->sTitle = t('No Games Found!');
            $this->_notFound();
        }
        else
        {
            $this->sTitle = t('Game - %0%', substr($oGame->description, 0, 100));
            $this->view->page_title = t('%0% Games Zone - %1%', $oGame->name, $oGame->title);
            $this->view->h1_title = $oGame->title;
            $this->view->meta_description = t('Flash Game - %0%', $this->sTitle);
            $this->view->meta_keywords = $oGame->keywords . $this->sMetaKeywords;
            $this->view->h2_title = $this->sTitle;
            $this->view->downloads = $this->oGameModel->getDownloadStat($oGame->gameId);
            $this->view->views = Stat::getView($oGame->gameId, 'Games');

            $this->view->game = $oGame;

            //Set Game Statistics
            Stat::setView($oGame->gameId, 'Games');
        }

        $this->output();
    }

    public function category()
    {
        $sCategory = str_replace('-', ' ', $this->httpRequest->get('name'));
        $sOrder = $this->httpRequest->get('order');
        $sSort = $this->httpRequest->get('sort');

        $this->iTotalGames = $this->oGameModel->category($sCategory, true, $sOrder, $sSort, null, null);
        $this->view->total_pages = $this->oPage->getTotalPages($this->iTotalGames, self::CATEGORIES_PER_PAGE);
        $this->view->current_page = $this->oPage->getCurrentPage();

        $oSearch = $this->oGameModel->category($sCategory, false, $sOrder, $sSort, $this->oPage->getFirstItem(), $this->oPage->getNbItemsByPage());
        $this->setMenuVars();

        $sCategoryTxt = substr($sCategory,0,60);
        if (empty($oSearch))
        {
            $this->sTitle = t('No "%0%" category found.', $sCategoryTxt);
            $this->_notFound();
        }
        else
        {
            $this->sTitle = t('Search by Category: "%0%" Game', $sCategoryTxt);
            $this->view->page_title = $this->sTitle;
            $this->view->h2_title = $this->sTitle;
            $this->view->h3_title = nt('%n% Game Found!', '%n% Games Found!', $this->iTotalGames);
            $this->view->meta_description = t('Search the Flash Game in the Category %0% - Community Dating Social Games', $sCategoryTxt);

            $this->view->games = $oSearch;
        }

        $this->manualTplInclude('index.tpl');
        $this->output();
    }

    public function search()
    {
        $this->sTitle = t('Search Game - Looking a new Game');
        $this->view->page_title = $this->sTitle;
        $this->view->h2_title = $this->sTitle;
        $this->output();
    }

    public function result()
    {
        $this->iTotalGames = $this->oGameModel->search($this->httpRequest->get('looking'), true, $this->httpRequest->get('order'), $this->httpRequest->get('sort'), null, null);

        $this->view->total_pages = $this->oPage->getTotalPages($this->iTotalGames, self::GAMES_PER_PAGE);
        $this->view->current_page = $this->oPage->getCurrentPage();

        $oSearch = $this->oGameModel->search($this->httpRequest->get('looking'), false, $this->httpRequest->get('order'), $this->httpRequest->get('sort'), $this->oPage->getFirstItem(), $this->oPage->getNbItemsByPage());
        $this->setMenuVars();

        if (empty($oSearch))
        {
            $this->sTitle = t('Sorry, Your search returned no results!');
            $this->_notFound();
        }
        else
        {
            $this->sTitle = t('Game - Your search returned');
            $this->view->page_title = $this->sTitle;
            $this->view->h2_title = $this->sTitle;
            $this->view->h3_title = nt('%n% Game Found!', '%n% Games Found!', $this->iTotalGames);
            $this->view->meta_description = t('Search - Free Games for Gamers, Flash Games, Free Online Games');
            $this->view->meta_keywords = t('search,game,free,flash,game site,flash game,games,gaming,online game');

            $this->view->games = $oSearch;
        }

        $this->manualTplInclude('index.tpl');
        $this->output();
    }

    public function download()
    {
        if ($this->httpRequest->getExists('id'))
        {
            $iId = $this->httpRequest->get('id');

            if (is_numeric($iId))
            {
                $sFile = @$this->oGameModel->getFile($iId);
                $sPathFile = PH7_PATH_PUBLIC_DATA_SYS_MOD . 'game/file/' . $sFile;

                if (!empty($sFile) && is_file($sPathFile))
                {
                    $sFileName = basename($sFile);
                    $this->file->download($sPathFile, $sFileName);
                    $this->oGameModel->setDownloadStat($iId);
                    exit(0);
                }
            }
        }

        $this->sTitle = t('Wrong download ID specified!');
        $this->_notFound();
        $this->manualTplInclude('game.tpl');
        $this->output();
    }

    /**
     * Sets the Menu Variables for the template.
     *
     * @access protected
     * @return void
     */
    protected function setMenuVars()
    {
        $this->view->top_views = $this->oGameModel->get(null, null, 0, 5, SearchCoreModel::VIEWS);
        $this->view->top_rating = $this->oGameModel->get(null, null, 0, 5, SearchCoreModel::RATING);
        $this->view->latest = $this->oGameModel->get(null, null, 0, 5, SearchCoreModel::ADDED_DATE);
        $this->view->categories = $this->oGameModel->getCategory(null, 0, 50, true);
    }

    /**
     * Set a Not Found Error Message with HTTP 404 Code Status.
     *
     * @return void
     */
    private function _notFound()
    {
        Framework\Http\Http::setHeadersByCode(404);
        $this->view->page_title = $this->sTitle;
        $this->view->h2_title = $this->sTitle;
        $this->view->error = $this->sTitle . '<br />' . t('Please return to the <a href="%0%">main game page</a> or <a href="%1%">the previous page</a>.', Uri::get('game','main','index'), 'javascript:history.back();');
    }
}
