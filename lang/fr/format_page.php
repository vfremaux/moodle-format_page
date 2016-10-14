<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Page format language file
 *
 * @author Jeff Graham
 * @contributor Valery Fremaux 2010
 * @version $Id: format_page.php,v 1.9 2012-07-30 15:02:47 vf Exp $
 * @package format_page
 **/

$string['page:addpages'] = 'Ajouter des nouvelles pages';
$string['page:editpages'] = 'Editer les pages et leurs réglages';
$string['page:editprotectedpages'] = 'Editer les pages protégées et leurs réglages';
$string['page:discuss'] = 'Discuter et commenter les pages';
$string['page:managepages'] = 'Réglage des pages';
$string['page:viewpagesettings'] = 'Régler la visibilité des pages';
$string['page:viewhiddenpages'] = 'Voir les pages cachées';
$string['page:viewpublishedpages'] = 'Voir les pages publiées';
$string['page:storecurrentpage'] = 'Mémoriser la dernière page visitée';
$string['page:quickbackup'] = 'Effectuer une sauvegarde rapide du cours';
$string['page:individualize'] = 'Individualiser';

$string['availability'] = 'Accès conditionnel';
$string['errorpageid'] = 'ID de page invalide';
$string['errorinsertaccessrecord'] = 'Impossible d\'enregistrer un droit d\'accès';
$string['erroractionnotpermitted'] = 'Vous devez avoir des droits particuleirs pour voir cette page.';
$string['errorpagebadname'] = 'Impossible de trouver le nom de la page';
$string['errorunkownpageaction'] = 'Action invalide: {$a}';
$string['errorunkownstructuretyp'] = 'Type de structure inconnu: {$a}';
$string['errorinsertpageitem'] = 'Erreur d\'insertion d\'un élément de page {$a}';
$string['errorblocksintancemodule'] = 'Le modle de bloc n\'a pas pu être créé';
$string['errorinvalidepageitem'] = 'Elément de page invalide';
$string['errorflexpageinstall'] = 'Votre installation du format page est incomplète. Vous devez installer et définir la surcharge de scripts "customscripts" dans votre fichier config.php.';

$string['activitylock'] = 'Verrouiller l\'accès à la page sur le score d\'une activité';
$string['activityoverride'] = 'Surcharger la page par une activité';
$string['addall'] = 'Montrer tout';
$string['addblock'] = 'Ajouter un bloc...';
$string['addexistingmodule'] = 'Ajouter une activité existante...';
$string['additem'] = 'Ajouter des éléments';
$string['addmodule'] = 'Ajouter une activité';
$string['addmoduleinstance'] = 'Ajouter une activité';
$string['addpage'] = 'Ajouter une page';
$string['addresources'] = 'Ajouter une ressource';
$string['addtemplate'] = 'Ajouter ce modèle';
$string['addtoall'] = 'Montrer à tous';
$string['administration'] = 'Administration du cours';
$string['applytoallpages'] = 'Appliquer à toutes les pages';
$string['asachildof'] = 'comme fils de {$a}';
$string['asamasterpage'] = 'comme page principale';
$string['asamasterpageafter'] = 'comme page principale après {$a}';
$string['asamasterpageone'] = 'comme première page principale';
$string['backtocourse'] = 'Retourner au cours';
$string['backupfailure'] = 'Une erreur est survenue pendant la sauvegarde.';
$string['backupsuccess'] = 'La sauvegarde s\'est déroulée sans erreur.';
$string['badmoverequest'] = 'Déplacement illicite. Ceci créerait une circularité infinie dans la hiérarchie';
$string['blockdirectorymissing'] = 'Il manque le répertoire de bloc';
$string['blocks'] = 'Les blocs sont des utilitaires, des raccourcis ou des applications connexes qui peuvent aider à la réalisation des objectifs du cours';
$string['bothbuttons'] = 'Lien précédent ET suivant';
$string['by'] = 'par';
$string['childpage'] = 'Page fille';
$string['choosepagetoedit'] = 'Editer la page...';
$string['choosepathtoimport'] = 'Choisir le répertoire des fichiers-ressources : ';
$string['choosetemplate'] = 'Choisir un modèle de page';
$string['cleanup'] = 'Nettoyer';
$string['cleanupadvice'] = 'Attention, vous allez détruire les instances d\'activités qui ne pourront plus être restituées. Voulez-vous continuer ?';
$string['cleanupreport'] = 'Nettoyage de : {$a->name}';
$string['cleanuptitle'] = 'Nettoyage du cours';
$string['clone'] = 'Dupliquer la page et préserver les activités';
$string['commands'] = 'Actions';
$string['confirmbackup'] = 'Exécuter la sauvegarde';
$string['confirmdelete'] = 'Voulez-vous vraiment supprimer la page : les pages filles de la page {$a} seront raccrochées à la page mère';
$string['content:loader'] = 'Chargeurs de référentiels documentaires';
$string['content:repository'] = 'Système de ressources centralisé';
$string['content:resource'] = 'Ressources CRS';
$string['couldnotmovepage'] = 'Erreur sérieuse, impossible de déplacer la page. Le format n\'a pu être mis à jour';
$string['couldnotretrieveblockinstance'] = 'Impossible de lire l\'instance du bloc : {$a}';
$string['coursecontent'] = 'Contenu de formation';
$string['coursemenu'] = 'Menu du cours';
$string['coursenotremapblockinstanceid'] = 'Impossible de reassigner l\'instance de bloc: {$a}';
$string['createitem'] = 'Créer des éléments';
$string['deephidden'] = 'Visible pour les auteurs des pages protégées';
$string['deletepage'] = 'Supprimer la page';
$string['disabled'] = 'Désactivé';
$string['disabled'] = 'Inactif';
$string['disabletemplate'] = 'Désactiver comme modèle';
$string['discuss'] = 'Commenter la page';
$string['discussion'] = 'Discussion';
$string['discussioncancelled'] = 'Annuler';
$string['discussionhascontent'] = 'La discussion est commencée';
$string['discussionhasnewcontent'] = 'La discussion a été modifiée depuis votre dernière visite';
$string['displaymenu'] = 'Afficher dans le menu de cours';
$string['displaytheme'] = 'Afficher dans les onglets';
$string['editpage'] = 'Editer la page';
$string['editpagesettings'] = 'Editer les réglages de page';
$string['editprotected'] = 'Cette page est verrouillée à l\'édition';
$string['enabletemplate'] = 'Activer comme modèle global';
$string['erroruninitialized'] = 'Ce cours n\'a apparemment aucune page affichable pour les utilisateurs.';
$string['existingmods'] = 'Vous pouvez réutiliser des activités qui ont déjà été créées et proposées sur d\'autres pages.';
$string['filename'] = 'Nom du fichier ressource';
$string['filterbytype'] = 'Filtrer par type d\'activité : ';
$string['formatpage'] = 'Format page';
$string['forum:eachuser'] = 'Chaque utilisateur démarre une discussion';
$string['forum:general'] = 'Forum global';
$string['forum:news'] = 'Forum des nouvelles';
$string['forum:qanda'] = 'Forum Qualité';
$string['forum:single'] = 'Simple discussion';
$string['forum:social'] = 'Social';
$string['fullclone'] = 'Dupliquer la page et les activités (copie complète)';
$string['globaltemplate'] = 'Est un modèle global';
$string['gotorestore'] = 'Aller à la gestion des fichiers de sauvegarde';
$string['hidden'] = ' Cette page n\'est pas publiée ';
$string['hiddenmark'] = '{Page non publiée}';
$string['hideresource'] = 'moins';
$string['hideshowmodules'] = 'Afficher ou cacher les modules';
$string['hideshowmodulesinstructions'] = 'Pour cacher un module, clickez sur l\'oeil dans la colonne Montrer/Cacher. Pour afficher le module, clickez sur l\'oeil fermé.';
$string['idnumber'] = 'Numéro d\'identification';
$string['importadvice'] = 'Vous pouvez générer un grand nombre de ressources dans le cours. Continuer ?';
$string['importresourcesfromfiles'] = 'Charger des fichiers comme ressources ';
$string['importresourcesfromfilestitle'] = 'Importer des ressources à partir des fichiers du cours';
$string['individualize'] = 'Individualiser';
$string['invalidblockid'] = 'Identifiant de bloc invalide: {$a}';
$string['invalidcoursemodule'] = 'Module de cours invalide: {$a}';
$string['invalidcoursemodulemod'] = 'Mod du Module de cours invalide : {$a}';
$string['invalidpageid'] = 'Identifiant de page invalide: {$a} , ou cette page n\'appartient pas à ce cours';
$string['invalidpageitemid'] = 'Identifiant d\'élément de page invalide: {$a}';
$string['lastmodified'] = 'Dernière modification le ';
$string['lastpost'] = 'Dernier message';
$string['layout'] = 'Mise en page';
$string['localdiscussionadvice'] = 'Les discussions enregistrées ici sont contextuelles à cette page de cours. Elles NE seront PAS sauvegardées dans les sauvegardes du cours.';
$string['locate'] = 'Localiser';
$string['locking'] = 'Activité verrou';
$string['lockingscore'] = 'Score min pour autoriser';
$string['lockingscoreinf'] = 'Score max pour autoriser';
$string['manage'] = 'Gérer les pages';
$string['managebackups'] = 'Manage Backups';
$string['managemods'] = 'Gérer les activités';
$string['masterpage'] = 'Page maître';
$string['menuitem'] = 'Element de menu';
$string['menuitemlocked'] = 'Module caché';
$string['menuitemunlocked'] = 'Module visible';
$string['menupage'] = 'Nom du Module';
$string['misconfiguredpageitem'] = 'Element de page mal configuré: {$a}';
$string['missingblockid'] = 'Impossible de récupérer le blockid du block_instance. pageitem->blockinstance erroné : {$a}?';
$string['missingcondition'] = 'Une condition non remplie vous empêche d\'accéder à cette page';
$string['missingcourseid'] = 'Identifiant de cours erroné';
$string['module'] = 'Element';
$string['movingpage'] = 'Page en cours de déplacement: {$a->name} (<a href="{$a->url}">Annuler le déplacement</a>)';
$string['mydiscussions'] = 'Mes discussions';
$string['myposts'] = 'Mes interventions';
$string['namepage'] = 'Page';
$string['navigation'] = 'Navigation';
$string['newpagelabel'] = 'Nouvelle page';
$string['newpagename'] = 'page-{$a}';
$string['newpagesettings'] = 'Nouvelle page';
$string['next'] = 'Suivante&gt;';  // pagename accessible via $a
$string['nextonlybutton'] = 'Page suivante seulement';
$string['noactivitiesfound'] = 'Aucune activité';
$string['nochildpages'] = 'Pas de page fille';
$string['nolock'] = 'Aucun';
$string['nomasterpageset'] = 'Pas de page maître';
$string['nomodules'] = 'Aucune activité disponible';
$string['nooverride'] = 'Page normale';
$string['nopages'] = 'Aucune page définie pour ce cours. Créez une page maître.';
$string['nopageswithcontent'] = 'Aucun contenu trouvé. Contactez votre tuteur ou un administrateur.';
$string['noparents'] = 'Aucune page parente disponible';
$string['noprevnextbuttons'] = 'Aucun lien';
$string['nopublicpages'] = 'Pas de pages publiques';
$string['nopublicpages_desc'] = 'Si coché, interdit l\'accès non connecté aux pages publiques. Les pages publiques sont lisibles uniquement par les personnes connectées.';
$string['occurrences'] = 'Utilisation';
$string['ornewpagesettings'] = 'Ou créer une nouvelle page avec les réglages';
$string['otherblocks'] = 'Autres blocs';
$string['override'] = 'Surcharge';
$string['page'] = 'Page ';
$string['pageformatonfrontpage'] = 'Montrer le format de page sur la première page';
$string['pageformatonfrontpagedesc'] = 'Ceci active le format page sur le portail. Si ce réglage est activé, alors les réglages de la <em>Front Page (frontpage)</em>, <em>Front page items when logged in (frontpageloggedin)</em>, and <em>Include a topic section (numsections)</em> settings will be ignored.';
$string['pagemenusettings'] = 'Affichage dans le menu paginé';
$string['pagename'] = 'Nom de la page';
$string['pagenameone'] = 'Nom de la page';
$string['pagenametwo'] = 'Nom à indiquer dans le menu de cours';
$string['pageoptions'] = 'Options de page';
$string['parent'] = 'Choisir une page parente dans le menu de cours';
$string['pluginname'] = 'Format Page';
$string['preferredcentercolumnwidth'] = 'Largeur centrale';
$string['preferredleftcolumnwidth'] = 'Largeur colonne gauche';
$string['preferredrightcolumnwidth'] = 'Largeur colonne droite';
$string['prefwidth'] = 'Largeur préférentielle';
$string['previous'] = '&lt;Précédente';  // pagename accessible via $a
$string['prevonlybutton'] = 'Page précédente seulement';
$string['protected'] = 'Page protégée';
$string['protectedmark'] = '{ Page cachée aux étudiants }';
$string['protectidnumbers'] = 'Protéger les numéros d\'identification';
$string['protectidnumbersdesc'] = 'Si activé les numéros d\'identification ne peuvent pas être modifiés. Cele peut être nécessaire lorsque les structures de cours sont générées automatiquement';
$string['public'] = ' Cette page est publique ';
$string['publish'] = 'Publier';
$string['published'] = ' Cette page est publiée aux participants ';
$string['quickbackup'] = 'Sauvegarde rapide';
$string['recurse'] = 'Copier toute la branche';
$string['regionwidthformat'] = 'Largeur numérique en pixels ou  *';
$string['relativeweek'] = 'Semaine (relative) d\'ouverture';
$string['relativeweekmark'] = '{ Non ouvert avant semaine +{$a} }';
$string['removeall'] = 'Cacher tout';
$string['removeforall'] = 'Cacher pour tous';
$string['reorganize'] = 'Réorganiser les pages';
$string['resource:blog'] = 'Blog';
$string['resource:directory'] = 'Répertoire';
$string['resource:file'] = 'Fichier';
$string['resource:html'] = 'Page HTML';
$string['resource:text'] = 'Page de texte';
$string['resource:themed'] = 'Contenu à thème';
$string['resource:url'] = 'URLs';
$string['resource:wikipage'] = 'Page Wiki';
$string['resourcename'] = 'Nom affiché de la ressource';
$string['searchauser'] = 'Rechercher des utilisateurs';
$string['seealltypes'] = 'Voir tous les types';
$string['setcurrentpage'] = 'Choisir la page courante :';
$string['settings'] = 'Réglages de page';
$string['showbuttons'] = 'Précédent &amp; suivant';
$string['showhide'] = 'Montrer/Cacher';
$string['showresource'] = 'plus...';
$string['template'] = 'Modèle';
$string['templating'] = 'Modèle global';
$string['thispageisnotpublished'] = '{ Cette page n\'est pas publiée }';
$string['thispagehasuserrestrictions'] = '{ Cette page a des resrictions utilisateur }';
$string['thispagehasgrouprestrictions'] = '{ Cette page a des restrictions de groupe }';
$string['thispagehasprofilerestrictions'] = '{ Cette page est restreinte sur profil }';
$string['thispagehaseditprotection'] = '{ Cette page ne peut être modifiée par les auteurs }';
$string['timelock'] = 'Verrouiller l\'accès à date';
$string['timerangemark'] = '{ Page non ouverte : ouverture de {$a->from} à {$a->to} }';
$string['unread'] = 'Non lus';
$string['updatesequencefailed'] = 'Erreur sérieuse sur la mise à jour de la séquence. Impossible de définir la séquence pour le format page';
$string['useasdefault'] = 'Utilisez ces réglages par défaut';
$string['usesindividualization'] = 'L\'individualisation par éléments du cours est activée';
$string['usespagediscussions'] = 'Les pages de discussion pédagogique attenante aux pages du cours sont activées';
$string['welcome'] = 'Accueil';

$string['reorder_help'] = '
## Réordonner les pages

Utilisez la représentation en arbre ci-contre pour déplacer les pages en les cliquant-tirant.<br/><br/>
Vous pouvez tirer une page jusqu\'à la bordure de gauche pour la remonter au premier niveau de hiérarchie. Elle sera alors la dernière.
';

$string['importresourcesfromfiles_help'] = '
## Génération de ressources `partir de fichers

Il peut être utile, lorsqu\'on charge beaucoup de fichiers dans le cours, de pouvoir optimiser la création de ressources Moodle `partir ce ces fichiers.

Habituellement, l\'utilisateur devra rejouer toute la séquence de création d\'une ressource pour chaque fichier.

Cette procdure permet d\'automatiser cette génération `partir des fichiers stockés dans un répertoire du dossier des fichiers du cours.

La procédure classique est la suivante :

1.  Télécharger un fichier ZIP contenant les ressources organisées en répertoires (attention, Moodle gère mal les répertoires contenant des caractères accentués ou des espaces).
2.  Dézipper l\'archive pour étaler les fichiers dans le dossier ds fichiers du cours.
3.  Aller sur la page désirée du cours (format page ou dérivés uniquement).
4.  Activer l\'import.
5.  Sélectioneer le répertoire où se trouvent les ressources.
6.  Donner des noms aux ressources pour leur insertion dans le cours.
7.  confirmez.';

$string['cleanup_help'] = '
## Fonction de nettoyage

La fonction de nettoyage permet de supprimer toutes les activités et ressources inutiles dans un cours au format flexipage. Toutes les activités pour lesquelles il est avéré qu\'aucune présence dans aucune page n\'apparaît seront supprimées du cours.

Les activités seront détruites

Les ressources seront supprimées du cours, mais pas les fichiers physiques stockés dans le répertoire des fichiers du cours.
';

$string['globaltemplate_help'] = '
# Pages modèles

Vous pouvez déclarer cette page comme modèle global de pages. Les modèles globaux sont disponibles dans tout le site pour permettre 
de créer des nouvelles pages déjà structurées avec des blocs et des activités selon l\'arrangement du modèle. Lorsque vous utilisez 
un modèle, les blocs et les activités seront toujours des nouvelles instances.
';

$string['prefwidth_help'] = '
vous pouvez choisir la largeur que vous voulez pour la colonne. Les valeurs dépendent du thème que vous utilisez. Si vous utilisez
un thème Bootstrap, comme Essential ou Bootstrapbase, vous devez utiliser des valeur de "span" entre 0 et 12, la somme de toutes les 
largeurs devant être égale à 12. Sinon, utilisez des valeurs en pixels d\'écran.
';

$string['editprotected_help'] = '
# Protection à l\'édition

Une page protégé ne peut être éditée que par les personnes disposant de la capacité adéquate dans le cours. Cette fonction permet de
verrouiller certaines pages d\'un cours type que l\'enseignant éditeur local ne peut altérer.
';

$string['activityoverride_help'] = '
# Surcharge du contenu de page par une activité.

Vous pouvez choisir de remplacer le contenu de cette page par la vue d\'une activité disponible du cours. Cette fonction est optimisée
si votre administrateur a installé les additions de navigation dans les écrans de l\'activité. Toutes les activités ne supportent pas 
obligatoirement cette faculté.
';

$string['blocks_help'] = '
# Ajouter un bloc

Choisissez un type de bloc à ajouter à cette page dans la liste. Les blocs sont des instances uniques qui n\'appartiennent qu\'à une page.
';

$string['pagediscussions_help'] = '
# Pages de discussion associées

Chaque page de cours peut avoir une page de discussion associée pour échanger sur le contenu.
';

$string['existingmods_help'] = '
# Activités existantes

Vous pouvez réutiliser une activité que vous avez déjà publiée ou que vous avez créée dans le cours. L\'onglet "Gérer les activités" vous
donne accès à un écran listant toutes les activités définies dans le cours, qu\'elles soient publiées ou non, avec des outils de gestion 
additionnels.
';

// Format page pfamily.
$string['pfamilynavigation'] = 'Aide à la navigation' ;
$string['pfamilysummaries'] = 'Résumés' ;
$string['pfamilyactivity'] = 'Accessoires d\'activité' ;
$string['pfamilystudenttools'] = 'Outils de l\'étudiant' ;
$string['teachertools'] = 'Outils de l\'enseignant' ;
$string['pfamilyconnectors'] = 'Connecteurs';
$string['pfamilysocial'] = 'Outils sociaux';
$string['pfamilyevaluationtools'] = 'Outils d\'évaluation';
$string['pfamilyresources'] = 'Ressources et documents';
$string['pfamilyworkshops'] = 'Outils de production';
$string['pfamilyadministration'] = 'Gestion';
$string['pfamilycontent'] = 'Contenu';
$string['pfamilytracking'] = 'Tracking et progresssion';
$string['pfamilymarketplace'] = 'Commercialisation des cours';
$string['pfamilyinteraction'] = 'Interactions';