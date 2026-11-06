<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @see     l10n/namespace.php
 */

$overrides = [
	// Users menu
	'Users' => 'Utilisateur·rices',
	'All Users' => 'Tous les comptes utilisateurs',
	'Add User' => 'Ajouter',
	// Users screen
	'Change role to&hellip;' => 'Modifier le rôle&hellip;',
	'&mdash; No role for this site &mdash;' => '&mdash; Aucun rôle dans ce livre &mdash;',
	'Search Users' => 'Rechercher des utilisateur·rices',
	'Email' => 'Courriel',
	// User roles, translated with context by translate_user_role()
	"User role\004Subscriber" => 'Abonné·e',
	"User role\004Collaborator" => 'Collaborateur·rice',
	"User role\004Author" => 'Auteur·rice',
	"User role\004Editor" => 'Éditeur·rice',
	"User role\004Administrator" => 'Administrateur·rice',
];

return $overrides;
