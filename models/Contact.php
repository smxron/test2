<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $firstName
 * @property string|null $lastName
 * @property string $createdAt
 * @property string $updatedAt
 */
class Contact extends ActiveRecord
{
    public $dealIds = [];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contact';
    }


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['firstName'], 'required'],
            [['firstName', 'lastName'], 'string', 'max' => 255],
            [['firstName', 'lastName'], 'trim'],
            [['createdAt', 'updatedAt'], 'integer'],
            [['dealIds'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'firstName' => 'First Name',
            'lastName' => 'Last Name',
            'createdAt' => 'Created At',
            'updatedAt' => 'Updated At',
            'dealIds' => 'Сделки',
        ];
    }

    public function getDeals()
    {
        return $this->hasMany(Deal::class, ['id' => 'dealId'])
            ->viaTable('dealContact', ['contactId' => 'id']);
    }

    public function afterFind()
    {
        parent::afterFind();
        // Загружаем выбранные сделки для формы
        $this->dealIds = $this->getDeals()->select('id')->column();
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $time = time();
            if ($insert) {
                $this->createdAt = $time;
            }
            $this->updatedAt = $time;
            return true;
        }
        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        // Обновляем связи многие-ко-многим
        $this->unlinkAll('deals', true);

        if (!empty($this->dealIds)) {
            foreach ($this->dealIds as $dealId) {
                $deal = Deal::findOne($dealId);
                if ($deal) {
                    try {
                        $this->link('deals', $deal);
                    } catch (\Exception $e) {
                        Yii::error("Error linking deal {$dealId} to contact {$this->id}: " . $e->getMessage());
                    }
                }
            }
        }
    }

}
