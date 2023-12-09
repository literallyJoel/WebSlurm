//=======================================//
//============ShadUI Helper=============//
//=====================================//
/*
This helper was provided as part of the ShadUI package.
This was not written by myself.
https://ui.shadcn.com/docs
*/

import { type ClassValue, clsx } from "clsx";
import { twMerge } from "tailwind-merge";

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}
