//This contains functions for validating inputs
import type { JobTypeParameter } from "./jobTypes";
//=======================================//
//================Users=================//
//=====================================//
export const validateEmail = (email: string): boolean => {
  return /[^\s@]+@[^\s@]+\.[^\s@]+/g.test(email);
};

export const validateName = (name: string) => {
  //Hard to validate names, so we just ensure something is in there
  return name.length !== 0;
};

export const validatePassword = (password: string) => {
  //Ensures password is at least 8 characters, contains a letter, a numer, and a special char.
  return /^(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{8,}$/.test(password);
};

export const validateParameters = (parameters: JobTypeParameter[]): number[] => {
  const invalidIndices: number[] = [];
  parameters.forEach((parameter, index) => {
    if (parameter.type === "Undefined") {
      invalidIndices.push(index);
    }
  });
  return invalidIndices;
};
