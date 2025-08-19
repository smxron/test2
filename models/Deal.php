<?php

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;

/**
 * @property int $id
 * @property string $name
 * @property float|null $amount
 * @property string $createdAt
 * @property string $updatedAt
 */
class Deal extends \yii\db\ActiveRecord
{
    public $contactIds = []; // Для множественного выбора контактов

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'deal';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'trim'],
            [['amount'], 'number', 'min' => 0],
            [['createdAt', 'updatedAt'], 'integer'],
            [['contactIds'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'amount' => 'Amount',
            'createdAt' => 'Created At',
            'updatedAt' => 'Updated At',
            'contactIds' => 'Контакты',
        ];
    }

    public function getContacts()
    {
        return $this->hasMany(Contact::class, ['id' => 'contactId'])
            ->viaTable('dealContact', ['dealId' => 'id']);
    }

    public function afterFind()
    {
        parent::afterFind();

        $this->contactIds = $this->getContacts()->select('id')->column();
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

        if (!$insert) {
            $this->unlinkAll('contacts', true);
        }

        if (!empty($this->contactIds)) {
            foreach ($this->contactIds as $contactId) {
                $contact = Contact::findOne($contactId);
                if ($contact) {
                    try {
                        $this->link('contacts', $contact);
                    } catch (\Exception $e) {
                        Yii::error("Error linking deal {$contactId} to contact {$this->id}: " . $e->getMessage());
                    }
                }
            }
        }
    }
}
