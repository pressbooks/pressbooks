/* global PB_Login */

const h1 = document.querySelector( 'h1' );
const login = document.querySelector( 'div#login' );
let subtitle = document.createElement( 'p' );
subtitle.classList.add( 'subtitle' );
if ( document.body.classList.contains( 'login-action-login' ) ) {
	subtitle.textContent = PB_Login.logInTitle;
} else if ( document.body.classList.contains( 'login-action-lostpassword' ) ) {
	subtitle.textContent = PB_Login.lostPasswordTitle;
} else if ( document.body.classList.contains( 'login-action-rp' ) ) {
	subtitle.textContent = PB_Login.resetPasswordTitle;
} else if ( document.body.classList.contains( 'login-action-resetpass' ) ) {
	subtitle.textContent = PB_Login.passwordResetTitle;
} else {
	subtitle.textContent = PB_Login.logInTitle;
}
document.body.insertBefore( h1, document.body.firstChild );
login.insertBefore( subtitle, login.firstChild );
