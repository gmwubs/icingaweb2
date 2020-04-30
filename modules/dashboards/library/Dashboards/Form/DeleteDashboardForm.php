<?php

namespace Icinga\Module\Dashboards\Form;

use Icinga\Authentication\Auth;
use Icinga\Module\Dashboards\Common\Database;
use ipl\Html\Html;
use ipl\Sql\Select;
use ipl\Web\Compat\CompatForm;

class DeleteDashboardForm extends CompatForm
{
    use Database;

    /** @var object $dashboard single dashboard from the database */
    protected $dashboard;

    /**
     * Create a dashboard delete Form
     *
     * @param object $dashboard  The dashboard that is deleted
     */
    public function __construct($dashboard)
    {
        $this->dashboard = $dashboard;
    }

    protected function assemble()
    {
        $this->add(
            Html::tag(
                'h1',
                null,
                Html::sprintf(
                    'Please confirm deletion of dashboard %s',
                    $this->dashboard->name
                )
            )
        );

        $this->addElement('input', 'btn_submit', [
            'type' => 'submit',
            'value' => 'Confirm Removal',
            'class' => 'btn-primary autofocus'
        ]);
    }

    protected function onSuccess()
    {
        if ($this->dashboard->type === 'public' && ! Auth::getInstance()->getUser()->isMemberOf('admin')) {
            throw new \ErrorException(sprintf(
                "You don't have a Permission to delete a public dashboard %s.",
                $this->dashboard->name
            ));
        }

        $select = (new Select())
            ->from('user_dashboard')
            ->where([
                'dashboard_id = ?' => $this->dashboard->id,
                'user_name = ?' => Auth::getInstance()->getUser()->getUsername()
            ]);

        $user = $this->getDb()->select($select)->fetch();

        if (Auth::getInstance()->getUser()->isMemberOf('admin')) {
            $this->getDb()->delete('dashlet', ['dashboard_id = ?' => $this->dashboard->id]);
            $this->getDb()->delete('user_dashlet', ['user_dashboard_id = ?' => $this->dashboard->id]);
            $this->getDb()->delete('user_dashboard', ['dashboard_id = ?' => $this->dashboard->id]);
            $this->getDb()->delete('dashboard', ['id = ?' => $this->dashboard->id]);
        } elseif ($this->dashboard->type === 'private' && $user) {
            $this->getDb()->delete('user_dashlet', ['user_dashboard_id = ?' => $this->dashboard->id]);
            $this->getDb()->delete('dashlet', ['dashboard_id = ?' => $this->dashboard->id]);
            $this->getDb()->delete('user_dashboard', ['dashboard_id = ?' => $this->dashboard->id]);
            $this->getDb()->delete('dashboard', ['id = ?' => $this->dashboard->id]);
        }
    }
}