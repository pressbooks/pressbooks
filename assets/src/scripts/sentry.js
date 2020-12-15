import * as Sentry from '@sentry/browser';
import { Integrations } from '@sentry/tracing';

/* global SentryParams */
Sentry.init( {
	dsn: SentryParams.dsn,
	integrations: [ new Integrations.BrowserTracing() ],
	tracesSampleRate: 1.0,
	environment: SentryParams.environment,
} );
