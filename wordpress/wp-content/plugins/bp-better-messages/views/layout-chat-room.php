<?php
defined( 'ABSPATH' ) || exit;
global $wpdb;
$message_id = false;
if(isset($_GET['message_id'])) $message_id = intval($_GET['message_id']);
$participants = BP_Better_Messages()->functions->get_participants( $thread_id );
if($message_id){
    $stacks = BP_Better_Messages()->functions->get_stacks( $thread_id, $message_id, 'to_message');
} else {
    $stacks = BP_Better_Messages()->functions->get_stacks( $thread_id );
}

$is_in_groups_now = bp_is_current_component('groups');
$user_id          = get_current_user_id();
$side_threads     = (BP_Better_Messages()->settings['combinedView'] === '1');
$group_id         = BP_Better_Messages()->functions->get_thread_meta( $thread_id, 'group_id' );
$group            = new BP_Groups_Group( $group_id );
$link             =  bp_get_group_permalink($group);
$subject          = BP_Better_Messages()->functions->get_thread_subject($thread_id);

if( trim($group->name) !== trim($subject) ){
    $subject = BP_Better_Messages()->functions->change_thread_subject( $thread_id, trim($group->name) );
}

if( $is_mini ) {
    $side_threads = false;
}

if( $is_in_groups_now ){
    $side_threads = false;
}
?><div class="bp-messages-wrap bp-messages-wrap-main" data-thread-id="<?php esc_attr_e($thread_id); ?>">

    <div class="bp-messages-threads-wrapper threads-hidden">
        <?php echo '<div class="bp-messages-column">'; ?>
        <div class="chat-header">
            <?php if( ! $is_mini && ! $is_in_groups_now ){ ?>
                <a href="<?php echo BP_Better_Messages()->functions->get_link(); ?>" class="back ajax" title="<?php _e( 'Back', 'bp-better-messages' ); ?>"><i class="fas fa-chevron-left" aria-hidden="true"></i></a>
            <?php }

            $_subject = $subject;
            if( $is_mini ) {
                $_subject = mb_strimwidth($_subject,0, 20, '...');
            } else {
                $_subject = '<a href="' . esc_url($link) . '">' . $_subject . '</a>';
            }

            echo '<strong title="' . $subject . '">' . $_subject . '</strong>';
            add_action('bp_better_messages_thread_pre_header', function( $thread_id, $participants, $is_mini, $type = 'thread' ){
                if( $is_mini ) return false;
                echo ' <a href="#" class="participants" title="' . __('Participants', 'bp-better-messages') . '"><i class="fas fa-users"></i> ' . ( count($participants['links']) + 1 ). '</a>';
            }, 10, 4);

            if( ! $is_mini ){
                echo '<a href="#" class="mobileClose"><i class="fas fa-window-close"></i></a>';

                $expandingButtons = apply_filters('bp_better_messages_thread_expanding_buttons', [], $thread_id, $participants, $is_mini, 'group' );
                if( count($expandingButtons) > 0 ){
                    ?>
                    <div class="expandingButtons" title="<?php _e('More', 'bp-better-messages'); ?>">
                        <i class="fas fa-ellipsis-v"></i>
                        <div class="bpbm-dropdown-menu">
                            <?php foreach( $expandingButtons as $slug => $expandingButton) {
                                echo $expandingButton;
                            } ?>
                        </div>
                    </div>
                <?php } ?>

                <?php do_action( 'bp_better_messages_thread_pre_header', $thread_id, $participants, $is_mini, 'group' ); ?>

            <?php } else {
                do_action( 'bp_better_messages_thread_pre_header', $thread_id, $participants, $is_mini, 'group' );
            } ?>
        </div>

        <?php if(! isset($_GET['mini'])) {
            $can_moderate = false; ?>
            <div class="participants-panel">
                <h4><?php _e('Participants', 'bp-better-messages'); ?></h4>
                <div class="bp-messages-user-list">
                    <?php foreach($participants['users'] as $user_id => $_user){
                        $user = get_userdata($user_id);
                        if( ! is_object( $user ) ) continue; ?>
                        <div class="user" data-id="<?php esc_attr_e($user_id); ?>" data-thread-id="<?php esc_attr_e($thread_id); ?>" data-username="<?php esc_attr_e($user->user_login); ?>">
                            <div class="pic">
                                <?php echo BP_Better_Messages_Functions()->get_avatar( $user_id, 30 ); ?>
                            </div>
                            <div class="name"><?php echo BP_Better_Messages()->functions->get_user_link( $user_id, 0 ); ?></div>
                            <div class="actions">
                                <?php if($user_id !== get_current_user_id() && $can_moderate){ ?>
                                    <a href="#" class="remove-from-thread" title="<?php _e('Exclude user from thread', 'bp-better-messages'); ?>"><i class="fas fa-ban"></i></a>
                                <?php } ?>
                            </div>
                            <div class="loading"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <div class="scroller scrollbar-inner thread" data-users="<?php echo implode( ',', array_keys( $participants[ 'users' ] ) ); ?>" data-users-json="<?php echo base64_encode(json_encode( $participants[ 'users' ] )); ?>" data-id="<?php echo $thread_id; ?>"<?php do_action('bp_better_messages_thread_div', $thread_id) ?>>
            <div class="loading-messages">
                <div class="bounce1"></div>
                <div class="bounce2"></div>
                <div class="bounce3"></div>
            </div>
            <div class="list">
                <?php if(count($stacks) == 0 || count($stacks) == 1 && $stacks[0]['user_id'] == 0){ ?>
                    <div class="empty-thread">
                        <i class="fas fa-comments"></i>
                        <span><?php esc_attr_e('Write the message to start conversation', 'bp-better-messages'); ?></span>
                    </div>
                <?php } else {
                    foreach ( $stacks as $stack ) {
                        echo BP_Better_Messages()->functions->render_stack( $stack );
                    }
                } ?>
            </div>
        </div>

        <?php do_action( 'bp_better_messages_thread_after_scroller', $thread_id, $participants, $is_mini ); ?>

        <span class="writing" style="display: none"></span>

        <?php if( apply_filters('bp_better_messages_can_send_message', BP_Groups_Member::check_is_member(get_current_user_id(), $group_id), get_current_user_id(), $thread_id ) ) { ?>
            <div class="reply">
                <form action="" method="POST">
                    <div class="message">
                        <?php do_action( 'bp_messages_before_reply_textarea', $thread_id ); ?>
                        <textarea placeholder="<?php esc_attr_e( "Write your message", 'bp-better-messages' ); ?>" name="message" autocomplete="off"></textarea>
                        <?php do_action( 'bp_messages_after_reply_textarea', $thread_id ); ?>
                    </div>
                    <div class="send">
                        <?php do_action('bp_better_messages_before_reply_send'); ?>
                        <button type="submit" title="<?php _e( 'Send Message', 'bp-better-messages' ); ?>"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
                        <?php do_action('bp_better_messages_after_reply_send'); ?>
                    </div>
                    <input type="hidden" name="action" value="bp_messages_send_message">
                    <input type="hidden" name="message_id" value="">
                    <input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
                    <?php wp_nonce_field( 'sendMessage_' . $thread_id ); ?>
                </form>

                <span class="clearfix"></span>

                <?php do_action( 'bp_messages_after_reply_form', $thread_id ); ?>
            </div>


            <?php do_action( 'bp_messages_after_reply_div', $thread_id ); ?>
        <?php } else {
            global $bp_better_messages_restrict_send_message;
            if( is_array($bp_better_messages_restrict_send_message) && ! empty( $bp_better_messages_restrict_send_message ) ){
                echo '<div class="reply">';
                echo '<ul class="bp-better-messages-restrict-send-message">';
                foreach( $bp_better_messages_restrict_send_message as $error ){
                    echo '<li>' . $error . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            }
        } ?>

        <div class="preloader"></div>

        <?php echo '</div></div>'; ?>

        <?php if( ! $is_mini && BP_Better_Messages()->settings['disableTapToOpen'] === '0' ){ ?>
            <div class="bp-messages-mobile-tap"><?php _e( 'Tap to open messages', 'bp-better-messages' ); ?></div>
        <?php } ?>
    </div>