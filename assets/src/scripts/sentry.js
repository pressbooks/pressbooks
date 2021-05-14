import * as Sentry from '@sentry/browser';
import { Integrations as TracingIntegrations } from '@sentry/tracing';

/* global SentryParams */
Sentry.init( {
	dsn: SentryParams.dsn,
	integrations: [ new TracingIntegrations.BrowserTracing() ],
	tracesSampleRate: parseInt(SentryParams.sample) / 10,
	environment: SentryParams.environment,
} );

if ( SentryParams.user ) {
	Sentry.configureScope( scope => scope.setUser( null ) );
	Sentry.setUser( {
		username: SentryParams.user.username,
		email: SentryParams.user.email,
	} );
}
