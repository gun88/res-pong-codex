<?php

class Res_Pong_User_Service {
    private $repository;
    private $configuration;

    public function __construct(Res_Pong_User_Repository $repository, Res_Pong_Configuration $configuration) {
        $this->configuration = $configuration;
        $this->repository = $repository;
    }

    public function get_events($request) {
        $start = $request->get_param('start');
        $end = $request->get_param('end');
        $user_id = $this->res_pong_get_logged_user_id();
        $events = $this->repository->get_events($start, $end, $user_id);
        foreach ($events as $event) {
            $event->event_countdown_minutes = $this->minutes_until($event->start_datetime);
            $event->status = $this->calculate_event_status($event);
        }
        return rest_ensure_response($events);
    }

    public function get_user_reservations_for_logged_user() {
        $user_id = $this->res_pong_get_logged_user_id();
        $reservations_by_user_id = $this->repository->get_reservations_by_user_id($user_id);
        return rest_ensure_response($reservations_by_user_id);
    }

    public function get_event_for_logged_user($request) {
        $event_id = $request->get_param('event_id');
        $user_id = $this->res_pong_get_logged_user_id();
        $event = $this->_get_event_for_logged_user($event_id, $user_id);
        return rest_ensure_response($event);
    }

    public function create_user_reservations_for_logged_user($request) {
        $event_id = $request->get_param('event_id');
        $user_id = $this->res_pong_get_logged_user_id();
        $event = $this->_get_event_for_logged_user($event_id, $user_id);

        if ($event->can_join) {
            $created_at = date('Y-m-d H:i:s');
            $this->repository->insert_reservation(['event_id' => $event_id, 'user_id' => $user_id, 'created_at' => $created_at]);
            $event = $this->_get_event_for_logged_user($event_id, $user_id);
        } else {
            if (!empty($event->status_message)) {
                $event->status_message['text'] = 'Impossibile completare la prenotazione. ' . $event->status_message['text'];
                $event->status_message['type'] = 'error';
            }
        }
        return rest_ensure_response($event);
    }

    public function delete_user_reservations_for_logged_user($request) {
        $event_id = $request->get_param('event_id');
        $user_id = $this->res_pong_get_logged_user_id();
        $event = $this->_get_event_for_logged_user($event_id, $user_id);

        if ($event->can_remove) {
            $this->repository->delete_reservation_by_user_and_event($user_id, $event_id);
            $event = $this->_get_event_for_logged_user($event_id, $user_id);
        } else {
            if (!empty($event->status_message)) {
                $event->status_message['text'] = 'Impossibile cancellare la prenotazione. ' . $event->status_message['text'];
                $event->status_message['type'] = 'error';
            }
        }
        return rest_ensure_response($event);
    }

    public function login($request) {
        $username = strtolower($request->get_param('username'));
        $password = $request->get_param('password');
        $remember = $request->get_param('remember');

        $user = $this->repository->get_enabled_user_by_username_or_email($username);
        if ($user && $user->enabled == 0) {
            return new \WP_REST_Response(['success' => false, 'error' => 'Utente disabilitato.', 'user' => null,], 403);
        }
        if (!$user || !password_verify($password, $user->password)) {
            return new \WP_REST_Response(['success' => false, 'error' => 'Username o password non validi.', 'user' => null,], 401);
        }

        $ttl = $remember ? 60 * 60 * 24 * 365 * 5 : 60 * 60 * 12; // 5 anni vs 12 ore
        $token = $this->res_pong_token_make((int)$user->id, $ttl);

        $this->res_pong_set_cookie($token, $ttl);

        $this->adjust_user($user);

        return new \WP_REST_Response(['success' => true, 'error' => null, 'user' => $user], 200);

    }

    public function logout() {
        $this->res_pong_clear_cookie();
        return new \WP_REST_Response(['success' => true], 200);
    }

    public function password_update_by_token(\WP_REST_Request $req) {

        try {
            $token = $this->base64url_decode($req->get_param('token'));
        } catch (Exception $e) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }

        list($ts, $rand) = explode('|', $token);
        if (time() > intval($ts)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }
        $user = $this->repository->get_enabled_user_by_token($token);
        if ($user === null) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }
        return $this->password_update($req, $user);

    }

    public function password_update_logged_user($req) {
        $user_id = $this->res_pong_get_logged_user_id();
        $user = $this->repository->get_user_by_id($user_id);
        return $this->password_update($req, $user);
    }

    public function password_reset(\WP_REST_Request $req) {
        $email = trim((string)$req->get_param('email'));
        if (empty($email) || !is_email($email)) {
            return new \WP_REST_Response(['success' => false, 'error' => 'Email non valida'], 400);
        }

        $user = $this->repository->get_enabled_user_by_email($email);
        if ($user) {
            $token = $this->generate_reset_token();
            $this->repository->update_user_token($user->id, $token);
            $url = $this->configuration->get('app_url') . '/#/password-update?token=' . $this->base64url_encode($token);
            $text = $this->configuration->get('reset_password_text');
            $placeholders = ['#email', '#username', '#last_name', '#first_name', '#category'];
            $replacements = [$user->email, $user->username, $user->last_name, $user->first_name, $user->category];
            $text = str_replace($placeholders, $replacements, $text);
            $message = $text . "\n\nClicca qui: " . $url;
            $subject = $this->configuration->get('reset_password_subject');
            wp_mail($email, $subject, $message);
        }
        // Rispondi comunque success per non rivelare se l'utente esiste
        return new \WP_REST_Response(['success' => true], 200);
    }

    public function get_logged_user() {
        $user_id = $this->res_pong_get_logged_user_id();
        $user = $this->repository->get_user_by_id($user_id);
        if (!$user->enabled) {
            return new \WP_REST_Response('Utente disabilitato', 401);
        }
        $this->adjust_user($user);
        return rest_ensure_response($user);
    }

    public function get_user_by_token(\WP_REST_Request $req) {
        try {
            $token = $this->base64url_decode($req->get_param('token'));
        } catch (Exception $e) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }

        list($ts, $rand) = explode('|', $token);
        if (time() > intval($ts)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }
        $user = $this->repository->get_enabled_user_by_token($token);
        if ($user === null) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Link scaduto'], 400);
        }

        $this->adjust_user($user);
        return rest_ensure_response($user);
    }

    public function res_pong_get_logged_user_id() {
        $t = $_COOKIE[RES_PONG_COOKIE_NAME] ?? null;
        if (!$t) return null;
        $uid = $this->token_parse($t);
        return $uid ?: null;
    }

    // ---------------------------------------------------------------

    private function adjust_user($user) {
        $this->add_monogram($user);
        $this->add_avatar($user);
        unset($user->password, $user->reset_token, $user->enabled);
    }

    private function generate_reset_token() {
        $expires = time() + 3600; // scade tra 1 ora
        $random = bin2hex(random_bytes(16));
        $token = $expires . '|' . $random;
        return $token;
    }

    private function get_event_for_logged_user_enrich_players($players, int $user_id) {
        foreach ($players as $player) {
            $player->current_user = $user_id == $player->user_id;
            $player->avatar = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQABAAD/2wCEAAkGBwgHBgkIBwgKCgkLDRYPDQwMDRsUFRAWIB0iIiAdHx8kKDQsJCYxJx8fLT0tMTU3Ojo6Iys/RD84QzQ5OjcBCgoKDQwNGg8PGjclHyU3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3Nzc3N//AABEIALwAyAMBIgACEQEDEQH/xAAcAAACAwEBAQEAAAAAAAAAAAAFBgMEBwIAAQj/xABJEAACAQMDAQUECAQBCAkFAAABAgMABBEFEiExBhNBUWEicYGRFDJCUqGxwdEHI3LwFTNigpKiwuHxFiU0Q1SDk7LSJCZjc+L/xAAZAQADAQEBAAAAAAAAAAAAAAABAgMABAX/xAAkEQACAgICAgICAwAAAAAAAAAAAQIRAyESMQRBIlETYRQycf/aAAwDAQACEQMRAD8AaFEXSRQw2AY3Yz7se6iEMKiBxDHlmPtjHTqevn7qgmtZJrONg6hs4TbnOc/l1qut1LZloGUGZjucheh8MV4s48psZ6ZzcKXuGDEBVUbNw5yORn31csrdNPhdpkjaaQHOBk58sjP9moLESzSGWRW3oN4ZjjjGOfP/AJVbuHF6wEfG8Yd+OPh40cjSjSN7BOsKszq9qMtzu3nqDwT+vyoVdPmVIwjdyxywXjaMY5pleKaSBrKINNsn9qQjGY/E8D3VQg020hd2lnIydijOPMjp8PnTY/irZqAtxtgdlnG7PHs+7gfiPlUWla69ksqMsrRFchC3PQ+fHXH40XmsraVwzB+7bao5HkB59ajOkw3N6m/csXLqrMODk8YOSetNPJH2EqnVri4PfYQLnCKxyQMcc+Ph1q9pWl32r77tIgq7j5AAjBx+PjQvUbdkuO7RSozxxx04GR48fjzioNT7U3FppkfZ3TCRNPJm4kU84YBdo+XX3Vbx4xfoH+EXarVks7h4LTbcTrwzrym7xAx1+FJl1cXtw7G5mklOeCzU6WehxwwYm5kb4YoZqeggjEe4VZSjHSLfglVgns52nudDv1mEYljX60ZbGR76cpdYt9YKXNhhiygGMAZRj4eg460lS6EIlyxycYobG1xouoRTQu2zdg+G4Dw91aVT0hJY3E1SGyuu4MQys8h3bD0Cgdfkfz99MXZhGis5ZO4Ykykb04GRx8+fdQGy1C3ks0vYWeSWSMMCN38lQD1z18qbuyrs+nKZdi7pO8Bbx8/gOf7FDBJ3skd9o3MGmxtLEEMz5kK4ypOW5446etQx2Ju1D2ErKvVeQeSPf/fFC+0N9eSS9zdP/LByqqOR18Pj/wAq92e1Z7ZjHhP5j8llx7seGDjpSZZQlO2Y9NBGbgTR7mMRYKi/XkJxzzUJvG3QwuzI4OW3DOSffjjkdfKic1qDIZVlPfc5YADrj19ff6UM1aa2jeWIzgzsQWLL4YBAHGf35rz5Sk3oeqDlhfXF1FEiHu5I8eIHvySSTx+lA+1wllmURQv3NuhDPtOfrY56edWLeeOygW4jkM10cFSDkhR0JwTjOenpUGvlmuYlhcSP3YaXoFbOeenPTPzrvjOXCmBbMU7UNu7Qagw8ZzQo/wC9V/W37zVbl/vPu+NUD4f1V3xekIdJ9dq9Xo+rH1xXq1mP0Y1vdPZI9vKFYHjI568GhFin0rUFTvGD9JCR0PP9/Cprm+WGVLU7DhwWQnjnp+VEtGgLyy3XsK0gUBDw27oePHp+NeTLTbKBExpZxog3gL7OdwHPUc5oFqkrQKz2xG+4zv3KOcHx6eGT8KMX0qlDHNnupZAr7W8APXp0NDNRtre0tg1vIFkVeN2wbiM9PCoYocpWzBGxnRtHjTB3yH+aXXBDcHH4jHwod9Hh/wARhhlbiVtyjIyAMjAz8Pka9fatAGtGPeNIGwcMApGWXPHj7/vGo7m8t/p3enu5JlKEMTwoyGOK7uKaopok1jdDO0EWV4+uWOMcHPl4H50Ps7jMrxmbulQkNI/ssfPx8fDGan1pzJvZYyR9mTd9YYGTS4ZXbIIUMHILFlAYg8Z8+ormzJMlJ7DCQSy6opRo5QQdoByeBgdDn40o6Lad52tvJ7gfVlfbnHBzxR2ykuGu4mmkIDEIxY8kEjPrXy8t1h7T30cQBJcONo65ANXwRaVhhuSQbKKyknGTQi7UbsUS7yJIh3siqR19ofrQy7niZiY3V89ADTcWeipfsD3kfBGKXtTgEsUink7eKYtVv7O3T+bKqO3gW5HwpbkvbSaYKknsnzFNCL7JZZKhp7NDuOzthu9oSB1C/fO8j3+fyrQdI2yxwO7CUFSvdEhgDlhg+P3fH30s9gNKs9Y7OQbb5Vnjnk2BGVmHQ8DPrR+w7OFEDzSSKmW9jGcjk58uc09V8mcPoqS2wtbwb4nuGb6pb2tqg8+z44xipLWKzlkjlETLOSH5TOTjnPgQOv8AeaJ3duZJA8pZgo2qhG054yBz0yB86+SP304hVI0bdlu8BCjA6DjjAOa5Zyt1QaojnMmCqDl1ySy7sDjHQ5/s8ULliS4Xu7hSjykbiTnHU9c58TwKMXWmXcBaESoI5MHesCnIHPGCCMdaX7GO8+mpFAO9jyGYo2dnjkZ8ag8DirC2SR27osaSkoZSpVUOQFxz0zj4eFevg09+wHCJhctnjgD8uaIOImZHuYz3gOFwysw6joD6UKkkfvr643P7U0m0t4jgc/AV0YZX2NDRiGoMXvJf6j+dVz9n+qpbn/tUp/zj+dQ/d+dekiJ3F9Zhg9c44+Veq7ZXLyy20JS3HIUfyh5+6vULMbLdHudZ7ySIOjLyNvoP1/GrtrqJRsmYTL44z/MBOMeWenTNUpp1ciWSJcrweevTA+PFCtKlzEzNnZ3h5Hy/QVwOPJjj530d1/JiXZG3USLkqAAf2NBO04jMSwhTu6KwwATzzirUUs7WEM5yI0GMNxnpzgeHFL2pytPJkAlMbkA6oPfUIRd6Afb/AE6XdGQoMbAHduHn6e+qsFuz6gMgkBD1PkM/p+VFdKN5dSmBUDQbeo6L0xz5/wDGvTW7W92xmj+ztwoPPTnp04q0JtOmNFHyRs2uz2iNrY2jIz7vwofptgL6GRp59sqnYkYbliQecDJ6+OOenFE711jA7lgGKhkA9SDj3/vS/HPNHed+5PU7vXPh7qnNfQJKmE5tM+jyXFwpaSOElk3nBIHOTVDtTHfxyC5sZkSb/vDwSibfPp09PCmOwvI72zkW7nZnKn2MD2ieQM/EZ91DrmAWsFzNMokLOXPGfY+qQfUAk1TDOSdM6cMYyg/sU9ZtLiTdG1s3eqPaaeUsCfTzoTY6RdTgLFcNFK0iIrISACW5/wBnJrQZLSdoAUuIXjA4MkZJx6kN7VVNOCJcCWeeJUjfCBRtXdjGQOpPxq6kWWMzTU9OlttTltp7mSZgeJWGePOrdnYyQFWkO+MfWJXpRLtJ3MmqSSB8spyNpqM7TbKe+Mgbxzim5Nok4JMJdkJU07tRZtGdo+k7ePNsLn8q2uN1NvFGr7TnIDD+xWJ9ltPnub+O6dGWCGbvO8YYBAPGPPOB8jWqRzmTT4MuXbICoFHtfjn+zSP5as58lLoITG3kjDIWMh3ZCFvrelBZLO4hmikO8tJ1w+GU/Hjofw8as3rzW6W7rb91JKOeOhwCTj5VTtL03bpE8ZdgMsMbgTxt5PTpUcqlF2hUwjO9xLKBNOqogxhh1HHxPn8aARW72l7J3ibkSRWASTO8EHnP4fCiep3yzZFqCxXBmY9D04Hj1A5NDoXLSTxXalAi7lVs+fTBPriklJy/saz7dKe7SWNidp2srEce7z5/Oo7hSunXuQQRAzbuSOFJ/PPyqrqdxHHYARtlwMYxwTkEYqK4v2/w/Uo952dw4A3eOMZ/vzow/QbMbmOZ5CfvVCPrKPT9q7Y5kYmvg+v/AKNekiYS7NxwvqVqZpjHtlUqNpbedw49K9Xuyybte07gH/6qM8jORuFepW6Ma57REhIXYi8jzzioLOPOntsX2e+6+mBRm2txNaSRJEGkb7RPhmoltjDbsJNmxm49DjJB59K4+WxrZba/jttLRpkk7wDYmNrDw4I93NBXuITGcxtGXblVx7Oc+BJqxNeFGUFGAUMDgdTxjPzqpau1zqUZYtvbP+T4OSPd+JqW4WwqwrpD7FMu0CLAOGx0BOc+7JqHUdV711YW52ou3fjKnOD1HTk+dErhHs9KucmQwyLtXcxyc+WOvTwpcsJXQMkIyAdzbvDjGD5+FSi+T5m6LazN38geOP8AyhRd+Fweg8fSl+6LtMUAU7vM9B148+tGp3gaIxMkcZX29wzkePJIHiR68ULGLa8UTbWQSE7T0chuh/Hxqq2wy2FdIjd54hE2JJFOSFJ69SMUc1LT4ZLC4VH3O0ZEYb2dxxx+NVJNSvnSELCqnZsJIBPw8vCvt7eS2rRyTQxsyhSAcEAE9MeYpJOTab0CLa6FQ6usmlOpc71TlvHHjQ4vqF5YILSF0jHtKBH0qtrOLLU51EZFvMcx5PVT/wA6v6briGyit5HaHAwXXy/s12xWrR1wyWtsUtR0+8jlSVlZnA5IGOapw3FxAkiy8ccfOj2vt3TOBeSSbD7W7z86WXZ7ufYp5JwD8ar2ieRpPRtPZ22jHZvTu8LF+67wIPDOMfn+Pvo3bSTJcWpll3MCNoUY2kDHPjnrx8cUvWGp6bFY28YlkAgjCxIRjJAIAPl4Uz6RbWzxi+bEokAYbMtnOePxrhSknZBdg68Nxe6g0fe7irZCqpODwKMdnNIKNIl5CyoYz1Yrk5GelDZlb/FJ5YY2LAYUBc+Xn49flRCy763c7ZpgzDn2S233/HHNXhNRdNCtbJrbS96XDTRmEnp7Jx1HngkcDilbVmeS8l3W47xQT7OTkcDxOc4JNMEmqXy7N9wfbYY2jw56fn8OtK+qHu9R74SblcOpbGDjnnFNllCZoqiAujTCPOEL+wXHh51LrFjFZ6TqUhlbeYMRrwSckDz4PUUPvu8huVA9oE7N3Xxor2rR4OyV2TIrL3Y+qeCSRz+fWudKpKgmIt1Y18+2f6a+txuJBIx/fNck4Ln/ADf3r0RQ12PVm7QacEGcSqT8Of0r1Tdil/8AuW0TxDPz/otXykfZjYUkdIdyLnHHJx9rgVPad7bkfSYpShJOFycg4x18xk5/4Upp2yjSNoXs2bzIfHjmuoe3UUKurWjsHOTnadvXgenTjpxXO8TsZ7DcsMl9ORCcgHPGP1IovZaP9E5m9uXOdysQcEcjAHTrSpafxB0y2gaKSxvHbGAQU8v3FVX/AIg6eRgw3Z4I9pU/epTxZJarQU0NupzJeiO1iaRSwVmCYIb35I8x8qXWs2hYquSBwPYGfP3fImqadu9HV1d471W8SoH71xJ250NnDkXG4HIJiwc/OhHBJKkjMMXqwW9reRQZUEBt+CwySAPHp4dKmi04WbQx7u+um+wm7PJ9/n60tz9r9CuXkBeZg42EtCcgemPdTl2M1Oy1SyaSzjkZUfc0jxkJk9ME+nlWlhm1SBZYstJMc5uGuEY5JDx7iSflmqOqGxuZ5u/mWNYR7Z3c7jyMDxpkvZu7t555MYVS2T7qwr/G5tSmvp92I1P8pc8FmyBn8K6IeLS+YE7HbVdNtdV0hBAhGzJRmOWx6nz56Vn9/a32n5WWJmQeI8+OPwFaV2TjDaZg/wCTY8DxwoCZ+OCan1Cwjkjbcg+NNy4SovjiskbRi9xezXLEFckjBzU+nW7pMjsOlNt/pMXelljAHoKi/wAMKHaByKLyKtBWLZU0K9cyzWsg3KjceHBP/P5U6dmtQn0u9wkji2kx3ig8FaRNGj+j9o078qkEsrR7mPGcD/e4rQ/oJtblA4IU+yS3qKZxTRHkN8V9ptpJFcTFC46vG2Qx6Z4z/ZqRprTULh2s7kSKPrrv9oN7v3pM1OSPT4JriYnbEMjPi2P7/Csxg1y/tNSN9b3Ekdy79QeoP2T5j0NK8HKNCtm/6hp1tLbrJDK+9sblQfX4/wCOaW791aVCwRwJMyPjnzx/y86XbL+J8TMn03ThuAXc8T4JYdWx4HPh0q/p2oQ6lEfoTb2+0CPaA9R4dKlki16CtFXU7qKzZHkQu7gmNCfXqfTqfhVOfWbjUAVuxALYfWRogVx+tULqX6XqU0shLKG4yfAdBQ/W5u4tVUAAN1HnVIQ+wWB+0s1lLe/9XQpEoTLKgwOT5fL50IIG2Q1PcOJZXlVO7DvnZ5CoPCUc5zgfhirgGjsII27UQs4YhBK+F9EbH518qX+HEkSdpoQ5wzKyoD54x+/WvUtGNLk/h5ZagRcm7dDKAQvdjjj31Wm/hbbH6uoN/wCj/wD1TjZapayQxRwzJIyqAQhB6e6rX0yIr9V/9WjaBsziX+FiZympkf8Akn/5VTk/hbL9nVFPvRv3rT2uYfNvlUTTwt1J+C0bDsyiT+F12PqahEfg1ULz+HV9bW8k8l/b93GuWPteHwrX5JIWz7WPhWffxD1gmSLT4nIjVgZ9pxuY9B8Bg/GinYHoE9jm0izuDYanaW08E3stNPCrFX+9yOmeK16KKJYxFGiwBAVCqoC49PSsIkcFjn62M+8Vpf8ADzXhqNkbC4cG6tOELZzImeD64zz6Y9atVCWGe1jldBu4wcfyyMg+nA+NYZpcObKEcZlnUn4GtA/ih2pWG2fSbAhpm4mkHRB4j+r+/Gs/0mcC0tjjOJcZPoCanIeJomg3ph7OyXcIBktJG7wH7oPtf7JooNUt9QhJUhJB1jbx9xpf/h9dRTLe2soA3Fy6vwOeo+TCqM8UltfvbqTsBYKfukHpU8mNTVk/Gyyx5JRGeOxN44CLweuBVHWp7PRZGNw2+XHswr9b4+VBJ9X1u3Xu4bsRR+ZC5/KgEe+7vxvke4mmdUeQ+px8TzU44tnZk8io6CvanTzbaXYJJjvdsjM3+dlCT8yav9j+2EdwsWl644BUhYblj5eDfvXP8QDNcfREjiYRqrPIzeAz0/vyrPZRhzjxrppHn4JPgmx57f6q8941nGSEj9qTyLn/AIfpSLvzvnJ9kDC++r0kjJaNI53HBOW5PTHjQ4LuitYU8WOfhWSLtlr6kUav0xkj1qxZXTW8iyF2jUnDMhwQM+lVrw+2qedR3PSKNeo60XTBY62+ODnK4zn0pc7Q3ZnuAi8qgwdvnzj+/SiGlXo/wxy5/mRrjA8R4Gmj+GluJNPvruZVbvp9q5Hgo9f6qjVDmYnd9x66ieaCQSJvV0OVIOMGt9+iW3/h4v8AUFRHTbI9bO3/APTFazGYdh767ue1Vv8ASZXYEuzFv6TXq0w2Fpbd5cQ29rG4VgGWIA9D418rWYtW9ncXfdzTyvFtB27MFj/s4A94J91EF0+Hd7bzSf8A7Jnx8s4qSFTtyeT5mrCocYNcrkyhTbTbP/w8fyqJtOtx9XvV/ondfyNFClcNH6VrZrA17CbSCSeO6nQRjcQ77wwA6ENkn4EVluqTG/muZJRh5WZwB4E+Faj2txH2evcrnKrn4sKya73lTJD1X64rswK1sjk7KcMjT27R5xPEcj1qJtUu7C6S80+cwOjeyw525GOQffXzd/PE0XBX64qLUUTvyONkn51ZiIieZ5kMzuzyMclmOST51zYXBluBFIFQNKS2D0yCKhs8tugb6ynPvFQOe5u0bphuaWQ0WO3Y+6SC+F3yIpLl93hlCcZ+VNXam1FtqMbnlbhfD76/uMfOk+x2/RFVBgRjOB5U9XH/AF12SiuF5nt/nleDn3jml9EM3wmpCTcxSXtyd5JXyFe0xg3aW2tYlCpBLH08WDqSfkKt7wi94OCeCPIih/ZhD/j1s0u7LytIPX2Sf0oItkfwbD3byciQuSdgjjUDzJZx+orOZ/8AK4p77eTbwg//ACJj3YBpFlXdcKPM4pyeHWNFm+jZrIRKfakaKIbuME5J/Ko4rG4gjsZ5YyschcKwI5PPrxXtU3H6Oqg8yGT3Acfoa6LTxtbRPMXixvHlkjn+/WsV9EM4336L9lBk/pXgu+VpPlUiLuMrjqSQD7v7NdKFRggGZD+FGgEMpMEO5Sdw9o499MPZPtrcaVtgnHe2fVk43p6jzoBPtefut311258qEjdE4YgjjIH5VOSGTP0bY3UGoWsVzaOHjkGVP6H1qfbWWfwz1prXUv8AD5mJgus7M/Zk6j54rVsVJ6HKd/xbS4+4f/aa9XtT4tJSPukfga9WMG4AxG3oR1yKsoD44OfLisp7OfxHjTR5rLVneG6SArbXa+3uYg43Yzg5xzS5Z9uNS0+YrBLLGk4VXw244zywzkBucZpPxsa0b55cDqfiK5YfHHUYwawy37S9qpYbm4OrXAhZQzc59kOE9nHqw6YJ58q0DsTrmrGDSYNd9o6gJPosxXBYpuJz4dB08ODzng8AWH+0lsk2iXySMFUQk7j5jn8xWMSygcAe2PDzrX+3kUn/AEWvVhydyqrEdcFsn9B8ax2QZiRn9mQLyPWr4emSyPYNuwS/eW/supzg+Iqo8huYmUH21Ps58/Wr0vOM+AxVGVAXWWI+0PxPlVRURI/tRXHmdjDyqPVVAfivNhJ3i+xN09DX3UzmONz1K8++lfQV2Mujy77VDngrtNOPYu5ImksZD7MmHQfexwR8Qc/6NInZ1t1nt8jkUdtrhrKWC6jJLxOHx6E4I+VImDNHlGibWLZrDVri1b2lYEofUf8AAg1Q7Ppu1dJpicBW8cccD/epn7dwiaC01O2HeBSoODjIbOPwakpb4RXWIwQ/cs3nkfWz/s0WtkoT/JiDva5RNhlPspz+1JMRzdxg/ZOfjinfWlC2dwXPRdoFIqHFwSOoBz8sftTIslSou3UjJd2s42t3SAFD0ILNx+NQzvBFaW6pNHJIrH2VYZAI8eaMaHBp964t7goJpdkUQ2ZbJGcjywcUBvSXkiiWNk+8du3I4zQ9h9Fi33fRRxyRn41HM/cqSBmV+npU6SqRiM4FclctuC7h6U4pSjhIJdzkk5r5qy4lQhRxGvHv5q5LEc5/MiqeoRzSfzfrjaoYr4YGKRoZFnQpWhv7WVM7kkUj5iv0FWDdkLU3mt2MCgnMwz7hyfwBPwreqjLsdFHVf+xyf018r7qn/ZGHiSB+Ir1AJjN7pesXMazahaT28KkZnlhZY0U+ZA6Ud1P+HF1FpcOpaZfQ39v9H3sNrKScZ9kY5HkOvpTV9PsL60Wzne4u4yfbW3ic97g5xkdB8aIXl1f3Y7u20/6PDswBO4Q46dE3Z+Yo8majNuz3ZbVby3gu3WeDSnILzRgksoJAO0HJwc9AcZzWj6Ra3Oo6nbyWd8YNP02Mi3KKXUORtwpkXJAXIOBxngnw6tV1iO2EEIs441GB7THAxjHHpRK20+9ndPpWoOAesdqohX8CW/Gs2zItJLJctNZXVyl3HLvikCgBgRjI44BHlisf1a3NrfT2rnJikZSfPBNa79G0zsvY3eomHBzlmJJaQnoMnnmsi1W4a+vJ7pxgyyM20etUxJk50BpkA8fxqlPF3asyP19KJSIhzUJibxOKsTBBfvIN5+tGcgeVTamB3Ufv/WoL1O5kO05DDBqa4YXForjkikHQU7KyNmRdvs+BNME67bdxn6q9aVOzt2sMxzznpTPJPHcwvEjBWdepzUx+0MvZ6VNb7NzabIAwQ91k9cfZPzyPhSnpmjRSi6EkeyeyimVvUKhIzV/shf8A+GaslvI2yORjG+716H501JpJTtbcAACK+spMg9M4wfzHzp+zglNYcjXpin2hnVrIkNkFwAfM+VJJbEkhHlj8R+1NGoSbNBsUfl2OeeCTS9b6de3rt9DtJpsv/wB2hYfMdOtBOjuSb6GbsrdNHZgQ2U8k6ybo8Oqpnxyc+HHhQvU9OvIZwL3uyCvsMo4PnyfGnHSOz66dp/d6hqNzGAuWW3t14by3YNLeu3yTyRpY2t+0KHaWuJDk+u0DGPhQUlYXGX0CxHDbKGbcc/VA8T5CvOtyzclYk+6OtdWMDMou5vrN9UE4CD96nZA/U8elVJlPuQerZqPesO5Buztbw9KmnnWOTYFx61BlnnYn1XPvGKVhQ6fwp00vcz6kw9mFe7Q4+03JPy4+NadmgvZHTDpOg20BGJGUvLx0ZucfLj4UXLDyNQl2VRT1Q+xGPNxj/WFerjVDzajHBlH/ALhX2gEHW2sx2mmQB7dprpYlWRGkVMvjnGfDNFtL1S21LbGIpobhukcidfcRkH50q2ySCbEKFpVOcRZLD4rlh8RXaSsYlWMI3mFALr/qgt8wK1m4j+tgft7R76uwxIOhx7qRbS+1COMGPvFbzllBH60eTVL60RTdJC4PQjgmtYKPvbnSZtV0fZase9gbf3f3/QetY7LGUdw3UceWK2kdpLNQDcBo8deMj96zjt1JYXOpi40uSKWOddx7rqrdDkdfLwq2KXolNClL1z4VQnMlxIIoB7XiT9keZqzqMpgCxrzI5wq/rXKbbOzZ2PIX2j941UQH31va2qbZ5pJJvurgfpUdnaXBV8R4jI4BPNTXS926u/Lexkn161YhkxSMdEq9mNUOHW3I8sHFFNN0PVhIPpUEpUcbhitGte7NtG+0FSoYfEVMpKsQxyD0rieeR3x8aIjz9mb+67toUKSKcb3H6U72C3saQm4MZmjXaXC5ycYP5V00oHXHvr4LgMMtwMY+NI80gy8PFLbRWGg6YmCbNZSvQvyB86JWncxYjUBcDAXGAKoyzyk8DbH5E81C+qW8BJkZcj7RbGaS5P2XjCMfQQvNrezjHjmlbWWW2jaU9VOBj31a1jXwtoJY4fZZtocg46Uo3WqXM7EyupU9QAKvhxuWyPkZVHRE7Q3E7SHCk+zsKjA8+Ko3sf8AMLwMePxqS9f+epXgrIG6dQava1p0lppdlfQMHhugwUHgoycMD5+Fd3SPNfYCLx3HsONr1JpsLrqdpkhl71efjVeR0l9mT2HqSJyrBWO0j6rDxpWFG7A3XO29gznJBgzx866AvT0ubM/+Q3/ypI7LdqwYfo+qSksg9iVhnI8j86Yxr+mt9W6HyP7VCWiqdkmoG4EtssjwM3eDaQpVevjz+Veqtc6jazPBNHODEkg3N92vUAlRNPj4M0fekdN53Ae4GrcaLkKw4HgOlLM/a23RSkI3MeiqM/jQ2fWtY1E93bRyRj1J/ShTCaCZbC2TfdTJGPN2C/gaC6p2w0KyBFuktw3hsG0fM4PyBpbtOyWr6iwMrOB68Uxad/Di3T2rli2euB1plEVsWRr+o9obz6FYi1sYz9uVuPmf2qv9HFiJg1wZ0SRt0rfa4AyPTitG1LQdM7P6NPeJApkRdsQx9s8D86yC/wBSWSweOLIONpB6g+RqsFsnLZVjdrq4e6boTtQeVSagDIbe0Xq5y3urqxj/AJMO0eArhnB1OeU9IY+PfVRDjWzlAw8TXEDHbnNRXshewg3HLMcmu48BEwetKxka72fnM+jWbE890ob4DH71cluVQg5HPT0pH7KatNJYtbEKpgbAOeoopNNHhjNP8M1504/I9OE04oITXgbo1QS6ngDeVWMHJzwTQOXUDczfR9MjaRj1kPRaPaJ2dTcLjVJe9kP1Qfqig40MpWRo2o6p/K0+MhPGaXgD3Vf0zscEn769driQdd+No+FMcDwxbURV2+S16S/iBwHHvpW2PQv9u7MR9m1ZBt7qVScevH7VmgkFah2yukuOzF4hwGKggZ8QwP6Vku7Fdvjf0PP8tfMnnk3RxyD6y8N7qZdT1HRZdJsNLVruX6OTM13FjAdwNyhWxwMAZ45B86U0bd7LcbvwNc2shDGKTON3jXQcx9uVXgN0P2/KqjzvbvtnX2avXDCXfEPLpXNjplxrMxtkKqyjLOwPGKD0ZbLugMW1SBHwys/tDPAGOT8OtN00GnLjgHHXaTUGldm47KxP83dcvw0uOB6AVzNp16oJRBID9qM5/CueTtlkqRK15pyW7WyAgM+T616g0ttOsmZIZV/qUj9K+1qCaHpvYSzh271z8KZbHQ7W2/ycKL8KMIo8qmVR5U+hNlOOzAGAAAPKrUVsEOanCgeFfT0P9QH41rZqRnv8RLh7m4h022YAxDvGJHQnp+HPxrK9c7L3Eve3sU8e8+1IuNoI8+M07aneTSaneSuQzSTHOc8e6vW4F1bHvemMYHTFBMNGXabqCqgjfh14X1r45AnncEYdeKFTgJcyIOivgH41akUGCI85MeTzVrJtEc8oe2hH3RjHxrhLkgAEZx0r4Il9asQwJ5GtQbRbstSe1jKoCFJyWqxb6nbSSZvHmYHyFVkRQMY4qZY0+6KR4kxllaHTRtZ0OIARTKhPXeCKZF1S2CnkEjwGaywQxk5KDNMp7Y65JAzvdgv5iNR+QqcvHRReVJdDe2oWJ5Z2T1DVWknsnGYroMPQ0Bh7TalJlJjbSr5Paxn/AHaaeyEVhrUbSXmlWCyD7UUW38M4pf4y+zfzX9ALWrqy/wAOuUabDmNlX2sgnwxWcm4QLksDRjtNaRf9K9TjQMkcc5CIpwFFCGtYQMBPxq0MfDoTLk/I7IpLwMCAepzX2a9VpUYD6y+16GvvcxjotfBGnlT0TO7e5UXDSSnhvLw5rU9E0WHSrNliczB23mQdW8selZRJGqxS4GK13sfM9z2dsXmO5thXPTgE0kho0WCnDHZx5V0qKfsjd54qw2A/1RUsMYb2iTn31MoU5ISV345zn4V8q3nLYPTGK9RMf//Z';
        }
    }

    private function _get_event_for_logged_user($event_id, int $user_id) {
        $event = $this->repository->get_event_by_id($event_id);
        if (!$event) {
            return new \WP_REST_Response(['error' => 'Evento non trovato'], 404);
        }
        $user = $this->repository->get_user_by_id_with_active_reservations($user_id, date('Y-m-d H:i:s'), $event->group_id);
        $players = $this->repository->get_reservations_by_event_id_with_user_data($event_id);
        $this->get_event_for_logged_user_enrich_players($players, $user_id);
        $other_events = $this->repository->get_next_and_previous_event($event_id, $event->start_datetime);
        if (!empty($other_events)) {
            $other_events = $other_events[0];
        } else {
            $other_events = [];
        }
        $event->players = $players;
        $event->players_count = count($players);;
        $event->other_events = $other_events;
        $event->event_countdown_minutes = $this->minutes_until($event->start_datetime);
        $event->status = $this->calculate_event_status($event);
        $event->user_status = $this->calculate_user_status($user, $event);
        $event->booked = $this->calculate_reservation_status($event);

        $out = $this->decide_event($event, $user);

        $event->status_message = $out['status_message'];
        $event->can_join = $out['can_join'];
        $event->can_remove = $out['can_remove'];
        return $event;
    }

    private function password_update(\WP_REST_Request $req, $user) {

        $password = $req->get_param('password');
        $confirm = $req->get_param('confirm');

        if (empty($password)) {
            return new \WP_REST_Response(['success' => false, 'message' => 'La password deve essere lunga almeno 6 caratteri'], 400);
        } else if ($password !== $confirm) {
            return new \WP_REST_Response(['success' => false, 'message' => 'Le password non coincidono'], 400);
        } else if (strlen($password) < 6) {
            return new \WP_REST_Response(['success' => false, 'message' => 'La password deve essere lunga almeno 6 caratteri'], 400);
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $this->repository->update_user_password($user->id, $hashed_password);
            $this->repository->update_user_token($user->id, null);
            return new \WP_REST_Response(['success' => true, 'message' => 'Password aggiornata'], 200);
        }
    }

    private function res_pong_clear_cookie() {
        // todo inline se usata solo in logout
        setcookie(RES_PONG_COOKIE_NAME, '', [
            'expires' => time() - 3600, 'path' => '/',
            'secure' => !RES_PONG_DEV,    // in dev su http
            'httponly' => true,
            'samesite' => RES_PONG_DEV ? 'Lax' : 'None' // DEV: lascia Lax
        ]);
    }

    private function res_pong_set_cookie(string $value, int $ttl) {
        $params = [
            'expires' => time() + $ttl,
            'path' => '/',
            'secure' => !RES_PONG_DEV,    // in dev su http
            'httponly' => true,
            'samesite' => RES_PONG_DEV ? 'Lax' : 'None' // DEV: lascia Lax
        ];
        setcookie(RES_PONG_COOKIE_NAME, $value, $params);
    }

    private function res_pong_token_make(int $userId, int $ttl): string {
        $exp = time() + $ttl;
        $payload = $userId . '|' . $exp;
        $sig = hash_hmac('sha256', $payload, RES_PONG_COOKIE_KEY);
        return base64_encode($payload . '|' . $sig);
    }

    private function token_parse(string $token) {
        $raw = base64_decode($token, true);
        if ($raw === false) return null;
        $parts = explode('|', $raw);
        if (count($parts) !== 3) return null;
        $uid = $parts[0];
        $exp = $parts[1];
        $sig = $parts[2];
        $payload = $uid . '|' . $exp;
        $calc = hash_hmac('sha256', $payload, RES_PONG_COOKIE_KEY);
        if (!hash_equals($calc, $sig)) return null;
        if ((int)$exp <= time()) return null;
        return (int)$uid;
    }

    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64url_decode($data) {
        $replaced = strtr($data, '-_', '+/');
        $padded = str_pad($replaced, strlen($replaced) % 4 === 0 ? strlen($replaced) : strlen($replaced) + (4 - strlen($replaced) % 4), '=', STR_PAD_RIGHT);
        return base64_decode($padded);
    }

    private function add_monogram($user) {
        $user->monogram = substr($user->first_name, 0, 1) . substr($user->last_name, 0, 1);
    }

    private function add_avatar($user) {
        $user->avatar = null;
        // $user->avatar = random_int(0, 1) ? "https://tennistavolomorelli.it/wp-content/uploads/fitet-monitor/players/587290.jpg" : null;
    }

    private function minutes_until($eventDate): int {
        // Create DateTime object for the event
        $event = DateTime::createFromFormat('Y-m-d H:i:s', $eventDate);

        // If the date format is invalid, return 0
        if (!$event) {
            return 0;
        }

        $now = new DateTime();

        // Difference in seconds
        $diffSeconds = $event->getTimestamp() - $now->getTimestamp();

        // Convert to whole minutes
        return (int)floor($diffSeconds / 60);
    }

    private function calculate_event_status($event) {
        // stati evento: closed, almost-closed, disabled, available, almost-full, full
        if ($event->event_countdown_minutes < 0) return 'closed';
        if (empty($event->enabled)) return 'disabled';
        if ($event->event_countdown_minutes < $this->configuration->get('almost_closed_minutes')) return 'almost-closed';
        if (empty($event->max_players)) return 'available'; // Nessun limite giocatori → disponibile
        if ($event->players_count >= $event->max_players) return 'full';
        if ($event->players_count >= $event->max_players - $this->configuration->get('almost_full_players')) return 'almost-full';
        return 'available';
    }

    private function calculate_user_status($user, $event) {
        // stato utente: disabled, timeout, max-booking-reached, enabled
        if (!$user->enabled) return 'disabled';
        if (!empty ($event->category) && strpos(strtolower($event->category), strtolower($user->category)) === false) return 'out-of-category';
        if ($user->active_reservations >= $this->configuration->get('max_active_reservations')) return 'max-booking-reached';
        if ($this->minutes_until($user->timeout) > 0) return 'timeout';
        return 'enabled';
    }

    private function calculate_reservation_status($event) {
        // stato prenotazione:  booked, not-booked
        // Se già registrato o l'utente corrente è tra i giocatori
        return ((!empty($event->players) && array_filter($event->players, function ($p) {
                return !empty($p->current_user);
            })));
    }

    private function decide_event($event, $user) {
        $status_message = null;
        $can_join = false;
        $can_remove = false;
        if ($event->user_status == 'disabled') {
            // se evento chiuso... messaggio e no azioni
            $status_message = ['type' => 'error', 'text' => 'Utente disabilitato.'];
        } else if ($event->status == 'closed') {
            // se evento chiuso... messaggio e no azioni
            $status_message = ['type' => 'secondary', 'text' => 'Evento terminato.'];
        } else if ($event->status == 'disabled') {
            // se evento disabilitato... messaggio e no azioni
            $status_message = ['type' => 'contrast', 'text' => 'Evento disabilitato.',];
        } else if ($event->status == 'almost-closed') {
            // e manca poco... messaggio e no azioni
            $status_message = ['type' => 'warn', 'text' => 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'];
        } else if ($event->booked) { // utente già prenotato, vediamo se possiamo concedere la cancellazione
            // se prenotato
            // do la possibilità di cancellare la prenotazione. Azione 'cancella' e messaggio
            $can_remove = true;
            $status_message = ['type' => 'success', 'text' => 'La tua prenotazione è attiva'];
        } else if ($event->user_status == 'out-of-category') { // IMPORTANTE! Da qui in poi lo user è sempre not-booked. Vediamo se può prenotare
            // se fuori categoria... messaggio e no azioni
            $status_message = ['type' => 'warn', 'text' => 'Evento riservato alle categorie: ' . $event->category];
        } else if ($event->status == 'full') {
            // evento al completo, inutile approfondire... messaggio e no azioni
            $status_message = ['type' => 'info', 'text' => "Evento al completo."];;
        } else if ($event->user_status == 'max-booking-reached') {
            // max numero prenotazioni raggiunto... messaggio e no azioni
            $active_reservations = $user->active_reservations;
            $status_message = ['type' => 'warn', 'text' => "Hai già " . ($active_reservations == 1 ? "una prenotazione" : "$active_reservations prenotazioni") . " attiva in un altra data per questa tipologia di evento. Non puoi effettuare altre prenotazioni."];
        } else if ($event->user_status == 'timeout') {
            // utente in castigo 😂... messaggio e no azioni
            $status_message = ['type' => 'warn', 'text' => "Sei in timeout! Potrai effettuare di nuovo la prenotazione solo dopo questa data: " . $user->timeout . "."];
        } else {
            // qui arriva (o dovrebbe arrivare se ho fatto bene i conti solo se:
            //  - l'utente non ha gi' prenotato questo evento
            //  - l'utente non ha blocchi (timeout, out-of-category, max-booking-reached, o disabled)
            //  - l'evento ha posti disponibili (available o almost-full)
            // do la possibilità di effettuare la prenotazione. Azione 'Prenota' e nessun messaggio
            $can_join = true;
        }
        return [
            'can_join' => $can_join,
            'can_remove' => $can_remove,
            'status_message' => $status_message
        ];
    }

    /*private function test() {
        $matrix = [
            // event-status   user-status           booked   can_join  can_remove  message
            ['closed', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['closed', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['closed', 'out-of-category', true, false, false, 'Evento terminato.'],
            ['closed', 'out-of-category', false, false, false, 'Evento terminato.'],
            ['closed', 'timeout', true, false, false, 'Evento terminato.'],
            ['closed', 'timeout', false, false, false, 'Evento terminato.'],
            ['closed', 'max-booking-reached', true, false, false, 'Evento terminato.'],
            ['closed', 'max-booking-reached', false, false, false, 'Evento terminato.'],
            ['closed', 'enabled', true, false, false, 'Evento terminato.'],
            ['closed', 'enabled', false, false, false, 'Evento terminato.'],

            ['almost-closed', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['almost-closed', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['almost-closed', 'out-of-category', true, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'out-of-category', false, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'timeout', true, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'timeout', false, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'max-booking-reached', true, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'max-booking-reached', false, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'enabled', true, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],
            ['almost-closed', 'enabled', false, false, false, 'Manca poco all\'inizio dell\'evento. Per comunicazioni urgenti, contattare il responsabile.'],

            ['disabled', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['disabled', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['disabled', 'out-of-category', true, false, false, 'Evento disabilitato.'],
            ['disabled', 'out-of-category', false, false, false, 'Evento disabilitato.'],
            ['disabled', 'timeout', true, false, false, 'Evento disabilitato.'],
            ['disabled', 'timeout', false, false, false, 'Evento disabilitato.'],
            ['disabled', 'max-booking-reached', true, false, false, 'Evento disabilitato.'],
            ['disabled', 'max-booking-reached', false, false, false, 'Evento disabilitato.'],
            ['disabled', 'enabled', true, false, false, 'Evento disabilitato.'],
            ['disabled', 'enabled', false, false, false, 'Evento disabilitato.'],

            ['available', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['available', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['available', 'out-of-category', true, false, true, null],
            ['available', 'out-of-category', false, false, false, 'Evento riservato alle categorie: VIP'],
            ['available', 'timeout', true, false, true, null],
            ['available', 'timeout', false, false, false, 'Sei in timeout! Potrai effettuare di nuovo la prenotazione solo dopo questa data: 2030-01-01.'],
            ['available', 'max-booking-reached', true, false, true, null],
            ['available', 'max-booking-reached', false, false, false, 'Hai raggiunto il numero massimo di prenotazioni per questa tipologia di evento.'],
            ['available', 'enabled', true, false, true, null],
            ['available', 'enabled', false, true, false, null],

            ['almost-full', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['almost-full', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['almost-full', 'out-of-category', true, false, true, null],
            ['almost-full', 'out-of-category', false, false, false, 'Evento riservato alle categorie: VIP'],
            ['almost-full', 'timeout', true, false, true, null],
            ['almost-full', 'timeout', false, false, false, 'Sei in timeout! Potrai effettuare di nuovo la prenotazione solo dopo questa data: 2030-01-01.'],
            ['almost-full', 'max-booking-reached', true, false, true, null],
            ['almost-full', 'max-booking-reached', false, false, false, 'Hai raggiunto il numero massimo di prenotazioni per questa tipologia di evento.'],
            ['almost-full', 'enabled', true, false, true, null],
            ['almost-full', 'enabled', false, true, false, null],

            ['full', 'disabled', true, false, false, 'Utente disabilitato.'],
            ['full', 'disabled', false, false, false, 'Utente disabilitato.'],
            ['full', 'out-of-category', true, false, true, null],
            ['full', 'out-of-category', false, false, false, 'Evento riservato alle categorie: VIP'],
            ['full', 'timeout', true, false, true, null],
            ['full', 'timeout', false, false, false, 'Evento al completo.'],
            ['full', 'max-booking-reached', true, false, true, null],
            ['full', 'max-booking-reached', false, false, false, 'Evento al completo.'],
            ['full', 'enabled', true, false, true, null],
            ['full', 'enabled', false, false, false, 'Evento al completo.'],
        ];

        foreach ($matrix as $row) {
            $event = new \stdClass();
            $event->status = $row[0];
            $event->user_status = $row[1];
            $event->booked = $row[2];
            $event->category = 'VIP';
            $user = new \stdClass();
            $user->timeout = '2030-01-01';

            $decide_event = $this->decide_event($event, $user);
            if ($decide_event['can_join'] != $row[3] || $decide_event['can_remove'] != $row[4] || $decide_event['status_message']['text'] != $row[5]) {
                error_log("error at " . json_encode($row) . " " . json_encode($decide_event));
            }

        }
        error_log("done___________________________");
        die(1);
    }*/

}
