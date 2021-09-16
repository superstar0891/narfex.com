<?php

use Phinx\Migration\AbstractMigration;

class AddNewLangs extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    addCustomColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Any other destructive changes will result in an error when trying to
     * rollback the migration.
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $create_keys = [
            'api_auth_user_banned' => 'api_auth_user_banned',
            'verification_you_not_pass' => 'verification_you_not_pass',
            'verification_you_pass_successfully' => 'verification_you_pass_successfully',
            'not_found' => 'not_found',
            'balance_not_found' => 'balance_not_found',
            'incorrect_code' => 'incorrect_code',
            'login_not_found' => 'login_not_found',
            'transaction_not_found' => 'transaction_not_found',
            'transfer_not_found' => 'transfer_not_found',
            'address_incorrect' => 'address_incorrect',
            'withdrawal_disabled' => 'withdrawal_disabled',
            '2fa_already_enabled' => '2FA already enabled.',
            'field_param_is_required' => '{field} param is required',
            'field_password_not_have_uppercase' => '{field} param must have at least one uppercase symbol',
            'field_password_not_have_lowercase' => '{field} param must have at least one lowercase symbol',
            'field_password_not_have_number' => '{field} param must have at least one number',
            'field_password_not_have_special_symbol' => '{field} param must have at least one special (! , ; _ -) symbol',
            'field_password_incorrect_length' => '{field} param must have at least 8 symbols',
            'field_max_len' => 'maximum length for param {field} is {max_len}',
            'field_min_len' => 'minimum length for param {field} is {min_len}',
            'field_int' => '{field} should be integer',
            'field_bool' => '{field} should be boolean',
            'field_double' => '{field} must be double',
            'field_positive' => '{field} must be positive number',
            'field_email' => '{field} is not a valid email address',
            'field_one_of' => '{field} must be one of {variants}',
            'field_max' => 'maximum value for param {field} is {max}',
            'field_min' => 'minimum value for param {field} is {min}',
            'field_username' => '{field} is incorrect',
        ];

        foreach ($create_keys as $name => $value) {
            $lang = new \Models\LangsModel();
            $lang->name = $name;
            $lang->lang = 'en';
            $lang->type = \Models\LangsModel::BACKEND_LANG;
            $lang->value = $value;
            $lang->save();
        }

        \Models\LangsModel::select(\Db\Where::and()
            ->set('name', \Db\Where::OperatorLike, 'module_%')
        )->delete();
    }
}
