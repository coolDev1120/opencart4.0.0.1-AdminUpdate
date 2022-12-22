<?php
namespace Opencart\Admin\Controller\User;
use \Opencart\System\Helper as Helper;
class Team extends \Opencart\System\Engine\Controller
{
    public function index(): void
    {
        $this->load->language('user/user');
        $this->document->setTitle("Teams");
        $url = '';
        $data['breadcrumbs'] = [];
        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token']),
        ];
        $data['breadcrumbs'][] = [
            'text' => "Teams",
            'href' => $this->url->link('user/team', 'user_token=' . $this->session->data['user_token'] . $url),
        ];
        $data['add'] = $this->url->link('user/team|form', 'user_token=' . $this->session->data['user_token'] . $url);
        $data['delete'] = $this->url->link('user/team|delete', 'user_token=' . $this->session->data['user_token']);

        $this->load->model('user/team');
        $team = $this->model_user_team->getTeams();

        if (count($team) != 0) {
            foreach ($team as $result) {
                $data['teams'][] = [
                    'id' => $result['id'],
                    'team_name' => $result['team_name'],
                    'edit' => $this->url->link('user/team|form', 'user_token=' . $this->session->data['user_token'] . '&id=' . $result['id'] . $url),
                ];
            }
        }

        $data['user_token'] = $this->session->data['user_token'];

        $data['list'] = $this->load->view('user/team_list', $data);
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('user/team', $data));
    }

    public function form(): void
    {
        $this->load->language('user/user');

        $this->document->setTitle("Teams");

        $data['text_form'] = !isset($this->request->get['id']) ? $this->language->get('text_add') : $this->language->get('text_edit');

        $url = '';

        $data['save'] = $this->url->link('user/team|save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('user/team', 'user_token=' . $this->session->data['user_token'] . $url);

        if (isset($this->request->get['id'])) {
            $this->load->model('user/team');
            $team_info = $this->model_user_team->getTeam($this->request->get['id']);
        }

        if (isset($this->request->get['id'])) {
            $data['id'] = (int) $this->request->get['id'];
        } else {
            $data['id'] = 0;
        }

        if (!empty($team_info)) {
            $data['team_name'] = $team_info['team_name'];
        } else {
            $data['team_name'] = '';
        }

        $data['user_token'] = $this->session->data['user_token'];
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('user/team_form', $data));
    }

    public function save(): void
    {
        $this->load->language('user/user');

        $json = [];

        if ((Helper\Utf8\strlen($this->request->post['teamname']) < 3) || (Helper\Utf8\strlen($this->request->post['teamname']) > 20)) {
            $json['error']['teamname'] = "Team name must be between 3 and 20 characters!";
        }

        $this->load->model('user/team');

        $team_info = $this->model_user_team->getTeamByTeamname($this->request->post['teamname']);

        if (!$this->request->post['id']) {
            if ($team_info) {
                $json['error']['warning'] = "warning: Team name already in use!";
            }
        } else {
            if ($team_info && ($this->request->post['id'] != $team_info['id'])) {
                $json['error']['warning'] = "warning: Team name already in use!";
            }
        }

        if (!$json) {
            if (!$this->request->post['id']) {
                $json['id'] = $this->model_user_team->addTeam($this->request->post);
            } else {
                $this->model_user_team->editTeam($this->request->post['id'], $this->request->post);
            }

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function delete(): void
    {
        $this->load->language('user/user');

        $json = [];

        if (isset($this->request->post['selected'])) {
            $selected = $this->request->post['selected'];
        } else {
            $selected = [];
        }

        if (!$json) {
            $this->load->model('user/team');

            foreach ($selected as $id) {
                $this->model_user_team->deleteTeam($id);
            }

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /////////////

    function list(): void {
        $this->load->language('user/user');

        $this->response->setOutput($this->getList());
    }

    protected function getList(): string
    {
        if (isset($this->request->get['sort'])) {
            $sort = $this->request->get['sort'];
        } else {
            $sort = 'username';
        }

        if (isset($this->request->get['order'])) {
            $order = $this->request->get['order'];
        } else {
            $order = 'ASC';
        }

        if (isset($this->request->get['page'])) {
            $page = (int) $this->request->get['page'];
        } else {
            $page = 1;
        }

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['action'] = $this->url->link('user/user|list', 'user_token=' . $this->session->data['user_token'] . $url);

        $data['users'] = [];

        $filter_data = [
            'sort' => $sort,
            'order' => $order,
            'start' => ($page - 1) * $this->config->get('config_pagination_admin'),
            'limit' => $this->config->get('config_pagination_admin'),
        ];

        $this->load->model('user/user');

        $user_total = $this->model_user_user->getTotalUsers();

        $results = $this->model_user_user->getUsers($filter_data);

        foreach ($results as $result) {
            $data['users'][] = [
                'user_id' => $result['user_id'],
                'username' => $result['username'],
                'status' => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
                'edit' => $this->url->link('user/user|form', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $result['user_id'] . $url),
            ];
        }

        $url = '';

        if ($order == 'ASC') {
            $url .= '&order=DESC';
        } else {
            $url .= '&order=ASC';
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $data['sort_username'] = $this->url->link('user/user|list', 'user_token=' . $this->session->data['user_token'] . '&sort=username' . $url);
        $data['sort_status'] = $this->url->link('user/user|list', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url);
        $data['sort_date_added'] = $this->url->link('user/user|list', 'user_token=' . $this->session->data['user_token'] . '&sort=date_added' . $url);

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        $data['pagination'] = $this->load->controller('common/pagination', [
            'total' => $user_total,
            'page' => $page,
            'limit' => $this->config->get('config_pagination_admin'),
            'url' => $this->url->link('user/user|list', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}'),
        ]);

        $data['results'] = sprintf($this->language->get('text_pagination'), ($user_total) ? (($page - 1) * $this->config->get('config_pagination_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_pagination_admin')) > ($user_total - $this->config->get('config_pagination_admin'))) ? $user_total : ((($page - 1) * $this->config->get('config_pagination_admin')) + $this->config->get('config_pagination_admin')), $user_total, ceil($user_total / $this->config->get('config_pagination_admin')));

        $data['sort'] = $sort;
        $data['order'] = $order;

        return $this->load->view('user/user_list', $data);
    }

    public function login(): void
    {
        $this->load->language('user/user');

        $this->response->setOutput($this->getLogin());
    }

    public function getLogin(): string
    {
        if (isset($this->request->get['user_id'])) {
            $user_id = (int) $this->request->get['user_id'];
        } else {
            $user_id = 0;
        }

        if (isset($this->request->get['page'])) {
            $page = (int) $this->request->get['page'];
        } else {
            $page = 1;
        }

        $data['user_logins'] = [];

        $this->load->model('user/user');

        $results = $this->model_user_user->getLogins($user_id, ($page - 1) * 10, 10);

        foreach ($results as $result) {
            $data['user_logins'][] = [
                'token' => $result['token'],
                'ip' => $result['ip'],
                'user_agent' => $result['user_agent'],
                'status' => $result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled'),
                'total' => $result['total'],
                'date_added' => date($this->language->get('datetime_format'), strtotime($result['date_added'])),
                'delete' => $this->url->link('user/user|deleteLogin', 'user_token=' . $this->session->data['user_token'] . '&user_login_id=' . $result['user_login_id']),
            ];
        }

        $login_total = $this->model_user_user->getTotalLogins($user_id);

        $data['pagination'] = $this->load->controller('common/pagination', [
            'total' => $login_total,
            'page' => $page,
            'limit' => 10,
            'url' => $this->url->link('user/user|Login', 'user_token=' . $this->session->data['user_token'] . '&user_id=' . $user_id . '&page={page}'),
        ]);

        $data['results'] = sprintf($this->language->get('text_pagination'), ($login_total) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($login_total - 10)) ? $login_total : ((($page - 1) * 10) + 10), $login_total, ceil($login_total / 10));

        return $this->load->view('user/user_login', $data);
    }

    public function deleteLogin(): void
    {
        $this->load->language('user/user');

        $json = [];

        if (isset($this->request->get['user_login_id'])) {
            $user_login_id = (int) $this->request->get['user_login_id'];
        } else {
            $user_login_id = 0;
        }

        if (isset($this->request->cookie['authorize'])) {
            $token = $this->request->cookie['authorize'];
        } else {
            $token = '';
        }

        if (!$this->user->hasPermission('modify', 'user/user')) {
            $json['error'] = $this->language->get('error_permission');
        }

        $this->load->model('user/user');

        $login_info = $this->model_user_user->getLogin($user_login_id);

        if (!$login_info) {
            $json['error'] = $this->language->get('error_login');
        }

        if (!$json) {
            $this->model_user_user->deleteLogin($user_login_id);

            // If current token in use then log user out.
            if ($login_info['token'] == $token) {
                $this->session->data['success'] = $this->language->get('text_success');

                $json['redirect'] = $this->url->link('common/login', '', true);
            } else {
                $json['success'] = $this->language->get('text_success');
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
