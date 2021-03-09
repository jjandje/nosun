<div class="chat-container">
	<div class="message">
		<div class="inner">
			<span>Chat - <?php echo get_the_date( 'd-m-Y' ); ?></span>
		</div>
	</div>
	<?php 	foreach ( $messages as $message ):
		$chat_message = $message[ 'chat_message' ];
		$chat_message_date = date( 'd-m-Y', strtotime( $message[ 'chat_message_date' ] ) );
		$chat_message_user = $message[ 'chat_message_user' ];

		$user_info = get_userdata( $chat_message_user[ 'ID' ] );
		$roles     = implode( ', ', $user_info->roles );


		if ( $chat_message_user[ 'ID' ] == get_current_user_id() && $roles != 'administrator' ):
			$class = '';
			$name = 'Jij';
		elseif ( $roles == 'administrator' ):
			$class = 'message--nosun';
			$name = 'noSun';
		else:
			$class = 'message--other';
			$name = $chat_message_user['user_firstname'];
		endif; ?>
		<div class="message <?= $class; ?>">
			<div class="message__profile">
				<?= $chat_message_user[ 'user_avatar' ]; ?>
				<span><?= $name; ?></span>
			</div>
			<div class="inner">
				<?php echo $chat_message; ?>
				<span><?php echo $chat_message_date; ?></span>
			</div>
		</div>
		<div class="clearfix"></div>
	<?php endforeach; ?>
</div>
