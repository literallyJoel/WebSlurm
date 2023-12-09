//=======================================//
//=============Form Inputs==============//
//=====================================//
/*
The ShadCN/UI library has been used for many of the UI elements, which are imported helper
https://ui.shadcn.com/docs
*/
import { useState } from "react";
import {
  CardTitle,
  CardHeader,
  CardContent,
  CardFooter,
  Card,
} from "@/shadui/ui/card";
import { Label } from "@/shadui/ui/label";
import { Input } from "@/shadui/ui/input";
import {
  SelectValue,
  SelectTrigger,
  SelectLabel,
  SelectItem,
  SelectGroup,
  SelectContent,
  Select,
} from "@/shadui/ui/select";
import { Button } from "@/shadui/ui/button";
import { Checkbox } from "@/shadui/ui/checkbox";
import {
  validateName,
  validateEmail,
  validatePassword,
} from "@/helpers/validation";
import { UseMutationResult } from "react-query";
import type {
  createAccountResponse,
  NewAccountObject,
} from "@/helpers/accountCreation";

//=======================================//
//================Props=================//
//=====================================//
/*
Because this is a component of a larger page, we need the server response in the parent.
For this reason, we pass the react-query mutator through from the parent so the result can
be accessed there.
*/
interface props {
  createAccount: UseMutationResult<
    createAccountResponse,
    unknown,
    NewAccountObject,
    unknown
  >;
}
export const CreateAccountView = ({ createAccount }: props): JSX.Element => {
  //=======================================//
  //==========Form Input Storage==========//
  //=====================================//
  //All the user input in the account creation form is stored in these state variables
  const [userName, setUserName] = useState("");
  const [userEmail, setUserEmail] = useState("");
  const [generateRandom, setGenerateRandom] = useState(false);
  //0 = user, 1 = admin
  const [privLevel, setPrivLevel] = useState(-1);
  const [pass, setPass] = useState("");
  const [confirmPass, setConfirmPass] = useState("");
  //=======================================//
  //=======Form Validation Storage========//
  //=====================================//
  /*
  Stores the validation state of all the form inputs in state variables
  so that visual feedback can be given to the user if they've given invalid input
  */
  const [isUsernameValid, setIsUsernameValid] = useState(true);
  const [isUserEmailValid, setIsUserEmailValid] = useState(true);
  const [isPassValid, setIsPassValid] = useState(true);
  const [isPrivLevelValid, setIsPrivLevelValid] = useState(true);

  //=======================================//
  //=======Form Validation Function=======//
  //=====================================//
  /*
  Uses the various validation funcions in the validation helper 
  file to validate user inputs on submission
  */
  function validateInput(): void {
    let valid = true;
    if (!validateName(userName)) {
      valid = false;
      setIsUsernameValid(false);
    }

    if (!validateEmail(userEmail)) {
      valid = false;
      setIsUserEmailValid(false);
    }

    if (!generateRandom && !validatePassword(pass)) {
      valid = false;
      setIsPassValid(false);
    }

    if (privLevel === -1) {
      valid = false;
      setIsPrivLevelValid(false);
    }

    //This is already dealt with in the UI in realtime but it helps to have a single response with all the info
    if (!(pass === confirmPass)) {
      valid = false;
    }

    if (valid) {
      createAccount.mutate({
        name: userName,
        email: userEmail,
        generatedPass: generateRandom,
        role: privLevel,
        password: pass,
      });
    }
  }
  //=======================================//
  //===============UI Code================//
  //=====================================//
  return (
    <Card className="max-w-2xl mx-auto">
      <CardHeader>
        <CardTitle>Create a new User</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="space-y-2 flex flex-col">
              <Label htmlFor="nameInput">Name</Label>
              <Label
                htmlFor="confirmPass"
                className={`text-xs text-red-500 ${
                  isUsernameValid ? "hidden" : ""
                }`}
              >
                Please enter a name.
              </Label>
              <Input
                id="nameInput"
                className={`mt-2 ${isUsernameValid ? "" : "border-red-500"}`}
                placeholder="Joel Vivian"
                value={userName}
                onChange={(e) => setUserName(e.target.value)}
                required
              />
            </div>
            <div className="space-y-2 flex flex-col">
              <Label htmlFor="email">Email</Label>
              <Label
                htmlFor="confirmPass"
                className={`text-xs text-red-500 ${
                  isUserEmailValid ? "hidden" : ""
                }`}
              >
                Please enter a valid email address.
              </Label>
              <Input
                id="email"
                placeholder="joel.vivian@domain.com"
                className={`${isUserEmailValid ? "" : "border-red-500"}`}
                value={userEmail}
                onChange={(e) => setUserEmail(e.target.value)}
                required
                type="email"
              />
            </div>
          </div>

          <div className="space-y-2 flex flex-col">
            <Label htmlFor="role">Role</Label>
            <Label
              htmlFor="role"
              className={`text-xs text-red-500 ${
                isPrivLevelValid ? "hidden" : ""
              }`}
            >
              Please select a role.
            </Label>
            <Select required onValueChange={(e) => setPrivLevel(parseInt(e))}>
              <SelectTrigger
                className={`${isPrivLevelValid ? "" : "border-red-500"}`}
              >
                <SelectValue placeholder="Select a role" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Roles</SelectLabel>
                  <SelectItem value="1">Admin</SelectItem>
                  <SelectItem value="0">User</SelectItem>
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>
          <div className="space-y-2 flex flex-col items-center">
            <Label htmlFor="randomGenerate">Generate Random Password?</Label>
            <Label htmlFor="randomGenerate" className="text-xs">
              This will be emailed to the user.
            </Label>

            <Checkbox
              id="randomGenerate"
              checked={generateRandom}
              onCheckedChange={(e) => {
                const val = e.valueOf();
                if (typeof val === "boolean") {
                  setGenerateRandom(val);
                }
              }}
            />
          </div>
          {!generateRandom && (
            <>
              <div className="space-y-2 flex flex-col">
                <Label htmlFor="password">Password</Label>
                <Label
                  htmlFor="confirmPass"
                  className={`text-xs text-red-500 ${
                    isPassValid ? "hidden" : ""
                  }`}
                >
                  Password must be at least 8 characters, 1 letter, 1 number,
                  and 1 special character.
                </Label>
                <Input
                  id="password"
                  value={pass}
                  className={`${isPassValid ? "" : "border-red-500"}`}
                  onChange={(e) => setPass(e.target.value)}
                  required={!generateRandom}
                  type="password"
                />
              </div>
              <div className="space-y-2 flex flex-col">
                <Label htmlFor="confirmPass">Confirm Password</Label>

                <Label
                  htmlFor="confirmPass"
                  className={`text-xs text-red-500 ${
                    confirmPass === pass ? "hidden" : ""
                  }`}
                >
                  Passwords do not match.
                </Label>

                <Input
                  id="confirmPass"
                  value={confirmPass}
                  className={confirmPass === pass ? "" : "border-red-500"}
                  onChange={(e) => setConfirmPass(e.target.value)}
                  required={!generateRandom}
                  type="password"
                />
              </div>
            </>
          )}
        </div>
      </CardContent>
      <CardFooter className="justify-center">
        <Button
          className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg-uol hover:shadow-inner"
          onClick={() => validateInput()}
        >
          Create User
        </Button>
      </CardFooter>
    </Card>
  );
};
