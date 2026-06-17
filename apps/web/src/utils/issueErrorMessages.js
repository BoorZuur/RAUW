export const ISSUE_ERROR_MESSAGES = {
    duplicate_target_not_found: 'De gekozen melding bestaat niet meer of is niet zichtbaar.',
    issue_not_matchable: 'Deze melding accepteert geen koppelingen meer (niet open of in behandeling).',
    cannot_duplicate_self: 'Je kunt je eigen melding niet als duplicaat koppelen.',
    cannot_join_duplicate_child: 'Je kunt alleen het hoofdverhaal volgen, niet een gekoppelde melding.',
    issue_not_canonical: 'Deze actie is alleen mogelijk op het hoofdverhaal.',
    issue_is_duplicate_child: 'Deze actie vereist het hoofdverhaal, niet een gekoppelde melding.',
    issue_not_linkable: 'Deze melding kan op dit moment niet gekoppeld worden.',
    issue_has_duplicates: 'Deze melding heeft al gekoppelde meldingen en kan niet zelf gekoppeld worden.',
    not_participant: 'Je volgt deze melding niet (meer).',
    issue_closed: 'Deze melding is gesloten; volgen is niet meer mogelijk.',
    default: 'Er ging iets mis. Probeer het opnieuw.',
};

export function getIssueErrorMessage(err) {
    const code = err?.response?.data?.code;

    if (code && ISSUE_ERROR_MESSAGES[code]) {
        return ISSUE_ERROR_MESSAGES[code];
    }

    const message = err?.response?.data?.message;
    if (message) {
        return message;
    }

    return ISSUE_ERROR_MESSAGES.default;
}
