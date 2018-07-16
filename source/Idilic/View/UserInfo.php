<?php
namespace SeanMorris\PressKit\Idilic\View;
class UserInfo extends \SeanMorris\Theme\View
{
}
__halt_compiler(); ?>

Information for user[<?=$user->id;?>] "<?=$user->username;?>":
	Id:		<?=$user->id;?>

	Username:	<?=$user->username;?>

	Is Admin?:	<?=$user->hasRole('\SeanMorris\Access\Role\Administrator')
		? 'yes'
		: 'no';?>

	Public Id:	<?=$user->publicId;?>
	
	Created:	<?=$user->created;?>

	Facebook ID:	<?=$user->facebookId;?>

	Roles:
<?php foreach($user->getSubjects('roles') as $role): ?>
	-		<?=$role->class;?>

<?php endforeach; ?>

<?php /* var_dump($user); */ ?>