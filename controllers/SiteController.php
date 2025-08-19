<?php

namespace app\controllers;

use app\models\Contact;
use app\models\Deal;
use Yii;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    public function beforeAction($action)
    {
        if (in_array($action->id, ['create-contact', 'update-contact', 'create-deal', 'update-deal'])) {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionRoutes()
    {
        $routes = Yii::$app->urlManager->rules;
        echo "<pre>";
        print_r($routes);
        echo "</pre>";
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex($menu = 'deals', $id = null)
    {
        $contacts = Contact::find()->all();
        $deals = Deal::find()->all();

        $selectedItem = null;
        if ($id) {
            $selectedItem = $menu === 'contacts'
                ? Contact::findOne($id)
                : Deal::findOne($id);
        }

        return $this->render('index.twig', [
            'menu' => $menu,
            'contacts' => $contacts,
            'deals' => $deals,
            'selectedItem' => $selectedItem,
        ]);
    }

    public function actionCreateContact()
    {
        $model = new Contact();
        $deals = Deal::find()->all();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['index', 'menu' => 'contacts', 'id' => $model->id]);
        }

        return $this->render('contact-form.twig', [
            'model' => $model,
            'deals' => $deals,
        ]);
    }

    public function actionUpdateContact($id)
    {
        $model = Contact::findOne($id);
        if (!$model) {
            throw new Exception('Контакт не найден');
        }

        $deals = Deal::find()->all();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->render('index.twig');
        }

        return $this->render('contact-form.twig', [
            'model' => $model,
            'deals' => $deals,
        ]);
    }

    public function actionDeleteContact($id)
    {
        $model = Contact::findOne($id);
        if (!$model) {
            throw new Exception('Контакт не найден');
        }

        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Контакт успешно удален');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при удалении контакта');
        }

        return $this->render('index.twig');
    }

    public function actionCreateDeal()
    {
        $model = new Deal();
        $contacts = Contact::find()->all();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Сделка успешно создана');
            return $this->render('index.twig');
        }

        return $this->render('deal-form.twig', [
            'model' => $model,
            'contacts' => $contacts,
        ]);
    }

    public function actionUpdateDeal($id)
    {
        $model = Deal::findOne($id);
        if (!$model) {
            throw new Exception('Сделка не найдена');
        }
        $contacts = Contact::find()->all();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->render('index.twig');
        }

        return $this->render('deal-form.twig', [
            'model' => $model,
            'contacts' => $contacts,
        ]);
    }

    public function actionDeleteDeal($id)
    {
        $model = Deal::findOne($id);
        if (!$model) {
            throw new Exception('Сделка не найдена');
        }
        if ($model->delete()) {
            Yii::$app->session->setFlash('success', 'Сделка успешно удалена');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при удалении сделки');
        }

        return $this->render('index.twig');
    }
}
