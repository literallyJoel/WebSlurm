//This contains functions for validating inputs

//=======================================//
//================Users=================//
//=====================================//

export function validateEmail(email: string): boolean {
  //Uses regex to validate emails
  return /[^\s@]+@[^\s@]+\.[^\s@]+/g.test(email);
}

export function validateName(name: string) {
  //Hard to validate names, so we just ensure something is in there
  return name.length !== 0;
}

export function validatePassword(password: string) {
  //Ensures password is at least 8 characters, contains a letter, a numer, and a special char.
  return /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(password);
}
