export function getCommentErrorMessage(err) {
    const status = err?.response?.status;
    const data = err?.response?.data;

    if (status === 422) {
        const contentError = data?.errors?.content?.[0];
        if (contentError) {
            return contentError;
        }
        return 'Reactie mag maximaal 2000 tekens bevatten.';
    }

    if (status === 403) {
        return 'Je mag deze reactie niet bewerken.';
    }

    if (status === 401) {
        return 'Je bent niet ingelogd. Log opnieuw in en probeer het nogmaals.';
    }

    if (data?.message && typeof data.message === 'string') {
        return data.message;
    }

    return 'Kon reactie niet opslaan. Probeer het opnieuw.';
}
