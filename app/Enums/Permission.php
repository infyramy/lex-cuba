<?php

namespace App\Enums;

class Permission
{
    // Notes
    const NOTES_VIEW = 'notes.view';

    const NOTES_CREATE = 'notes.create';

    const NOTES_EDIT = 'notes.edit';

    const NOTES_DELETE = 'notes.delete';

    // Case Summaries
    const CASE_SUMMARIES_VIEW = 'case_summaries.view';

    const CASE_SUMMARIES_CREATE = 'case_summaries.create';

    const CASE_SUMMARIES_EDIT = 'case_summaries.edit';

    const CASE_SUMMARIES_DELETE = 'case_summaries.delete';

    // Questions
    const QUESTIONS_VIEW = 'questions.view';

    const QUESTIONS_CREATE = 'questions.create';

    const QUESTIONS_EDIT = 'questions.edit';

    const QUESTIONS_DELETE = 'questions.delete';

    // Categories (Topics)
    const CATEGORIES_VIEW = 'categories.view';

    const CATEGORIES_CREATE = 'categories.create';

    const CATEGORIES_EDIT = 'categories.edit';

    const CATEGORIES_DELETE = 'categories.delete';

    // Topic Links
    const TOPIC_LINKS_VIEW = 'topic_links.view';

    const TOPIC_LINKS_CREATE = 'topic_links.create';

    const TOPIC_LINKS_EDIT = 'topic_links.edit';

    const TOPIC_LINKS_DELETE = 'topic_links.delete';

    // Free Links
    const FREE_LINKS_VIEW = 'free_links.view';

    const FREE_LINKS_CREATE = 'free_links.create';

    const FREE_LINKS_EDIT = 'free_links.edit';

    const FREE_LINKS_DELETE = 'free_links.delete';

    // Media
    const MEDIA_VIEW = 'media.view';

    const MEDIA_UPLOAD = 'media.upload';

    const MEDIA_DELETE = 'media.delete';

    // Admin Users
    const USERS_VIEW = 'users.view';

    const USERS_CREATE = 'users.create';

    const USERS_EDIT = 'users.edit';

    const USERS_DELETE = 'users.delete';

    // Members (mobile app users)
    const MEMBERS_VIEW = 'members.view';

    const MEMBERS_CREATE = 'members.create';

    const MEMBERS_EDIT = 'members.edit';

    const MEMBERS_DELETE = 'members.delete';

    // Subscription Packages
    const PACKAGES_VIEW = 'packages.view';

    const PACKAGES_CREATE = 'packages.create';

    const PACKAGES_EDIT = 'packages.edit';

    const PACKAGES_DELETE = 'packages.delete';

    // Roles
    const ROLES_VIEW = 'roles.view';

    const ROLES_CREATE = 'roles.create';

    const ROLES_EDIT = 'roles.edit';

    const ROLES_DELETE = 'roles.delete';

    // Settings
    const SETTINGS_VIEW = 'settings.view';

    const SETTINGS_EDIT = 'settings.edit';

    // Statutes
    const STATUTES_VIEW = 'statutes.view';

    const STATUTES_CREATE = 'statutes.create';

    const STATUTES_EDIT = 'statutes.edit';

    const STATUTES_DELETE = 'statutes.delete';

    // Audit
    const AUDIT_READ = 'audit.read';

    public static function all(): array
    {
        return [
            self::NOTES_VIEW, self::NOTES_CREATE, self::NOTES_EDIT, self::NOTES_DELETE,
            self::CASE_SUMMARIES_VIEW, self::CASE_SUMMARIES_CREATE, self::CASE_SUMMARIES_EDIT, self::CASE_SUMMARIES_DELETE,
            self::QUESTIONS_VIEW, self::QUESTIONS_CREATE, self::QUESTIONS_EDIT, self::QUESTIONS_DELETE,
            self::CATEGORIES_VIEW, self::CATEGORIES_CREATE, self::CATEGORIES_EDIT, self::CATEGORIES_DELETE,
            self::TOPIC_LINKS_VIEW, self::TOPIC_LINKS_CREATE, self::TOPIC_LINKS_EDIT, self::TOPIC_LINKS_DELETE,
            self::FREE_LINKS_VIEW, self::FREE_LINKS_CREATE, self::FREE_LINKS_EDIT, self::FREE_LINKS_DELETE,
            self::MEDIA_VIEW, self::MEDIA_UPLOAD, self::MEDIA_DELETE,
            self::USERS_VIEW, self::USERS_CREATE, self::USERS_EDIT, self::USERS_DELETE,
            self::MEMBERS_VIEW, self::MEMBERS_CREATE, self::MEMBERS_EDIT, self::MEMBERS_DELETE,
            self::PACKAGES_VIEW, self::PACKAGES_CREATE, self::PACKAGES_EDIT, self::PACKAGES_DELETE,
            self::ROLES_VIEW, self::ROLES_CREATE, self::ROLES_EDIT, self::ROLES_DELETE,
            self::STATUTES_VIEW, self::STATUTES_CREATE, self::STATUTES_EDIT, self::STATUTES_DELETE,
            self::SETTINGS_VIEW, self::SETTINGS_EDIT,
            self::AUDIT_READ,
        ];
    }

    /**
     * Permissions for the Content Manager role.
     * Can manage content and members, but not admin users, roles, or system settings.
     */
    public static function contentManagerPermissions(): array
    {
        return [
            self::NOTES_VIEW, self::NOTES_CREATE, self::NOTES_EDIT, self::NOTES_DELETE,
            self::CASE_SUMMARIES_VIEW, self::CASE_SUMMARIES_CREATE, self::CASE_SUMMARIES_EDIT, self::CASE_SUMMARIES_DELETE,
            self::QUESTIONS_VIEW, self::QUESTIONS_CREATE, self::QUESTIONS_EDIT, self::QUESTIONS_DELETE,
            self::CATEGORIES_VIEW, self::CATEGORIES_CREATE, self::CATEGORIES_EDIT, self::CATEGORIES_DELETE,
            self::TOPIC_LINKS_VIEW, self::TOPIC_LINKS_CREATE, self::TOPIC_LINKS_EDIT, self::TOPIC_LINKS_DELETE,
            self::FREE_LINKS_VIEW, self::FREE_LINKS_CREATE, self::FREE_LINKS_EDIT, self::FREE_LINKS_DELETE,
            self::STATUTES_VIEW, self::STATUTES_CREATE, self::STATUTES_EDIT, self::STATUTES_DELETE,
            self::MEDIA_VIEW, self::MEDIA_UPLOAD, self::MEDIA_DELETE,
            self::MEMBERS_VIEW,
        ];
    }
}
