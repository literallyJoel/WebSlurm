import Nav from "@/components/Nav";
import { useMutation } from "react-query";
import {
  createAccount,
  type CreateAccountRequest,
  validateEmail,
  validateName,
  validatePassword,
  createInitialUser,
} from "@/helpers/users";
import { useRef, useState } from "react";

import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/shadui/ui/card";
import { Label } from "@/components/shadui/ui/label";
import { Input } from "@/components/shadui/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
} from "@/components/shadui/ui/select";
import { Checkbox } from "@/components/shadui/ui/checkbox";
import { Button } from "@/components/shadui/ui/button";
import { SelectTrigger, SelectValue } from "@radix-ui/react-select";
import { Link } from "react-router-dom";

interface props {
  isSetup?: boolean;
}
const CreateAccount = ({ isSetup }: props): JSX.Element => {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [generatePass, setGeneratePass] = useState(false);
  const token = localStorage.getItem("token") ?? "";
  const [role, setRole] = useState(isSetup ? 1 : 0);
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");

  const [isNameValid, setIsNameValid] = useState(true);
  const [isEmailValid, setIsEmailValid] = useState(true);
  const [isPasswordValid, setIsPasswordValid] = useState(true);
  const creationSuccessRef = useRef<HTMLAnchorElement>(null);
  const creationFailureRef = useRef<HTMLAnchorElement>(null);
  const _createAccount = (): void => {
    setIsNameValid(validateName(name));
    setIsEmailValid(validateEmail(email));
    setIsPasswordValid(!generatePass && validatePassword(password));

    if (
      isEmailValid &&
      isPasswordValid &&
      isNameValid &&
      password === confirmPassword
    ) {
      callCreateAccount.mutate(
        {
          name: name,
          email: email,
          role: role,
          generatedPass: generatePass,
          password: password,
        },
        {
          onSuccess: (data) => {
            if (isSetup) {
              window.location.reload();
            } else if (data.generatedPass) {
              localStorage.setItem("gpass", data.generatedPass);
              creationSuccessRef.current?.click();
            } else {
              creationSuccessRef.current?.click();
            }
          },
          onError: (error) => {
            console.log(error);
            creationFailureRef.current?.click();
          },
        }
      );
    }
  };

  const callCreateAccount = useMutation(
    "createAccount",
    (newAccount: CreateAccountRequest) => {
      if (isSetup) {
        return createInitialUser(
          newAccount.name,
          newAccount.email,
          newAccount.role,
          newAccount.password!
        );
      }
      if (newAccount.generatedPass) {
        return createAccount(
          newAccount.name,
          newAccount.email,
          newAccount.role,
          token,
          newAccount.generatedPass
        );
      } else {
        return createAccount(
          newAccount.name,
          newAccount.email,
          newAccount.role,
          token,
          false,
          newAccount.password!
        );
      }
    }
  );

  return (
    <div className="flex flex-col h-screen">
      <Nav />
      {/* These exist because I can't use the navigate hook so I have to create invisible links and click them programatically. */}
      <Link
        to="/accounts/create/success"
        className="hidden"
        ref={creationSuccessRef}
      />
      <Link
        to="/accounts/create/failure"
        className="hidden"
        ref={creationFailureRef}
      />
      <div className="mt-10 flex-grow mb-10">
        <Card className="max-w-2xl mx-auto">
          <CardHeader>
            <CardTitle>Create a new User</CardTitle>
            {isSetup && (
              <CardDescription>
                Create an admin account to get started
              </CardDescription>
            )}
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2 flex flex-col">
                  <Label htmlFor="nameInput">Name</Label>
                  <Label
                    className={`text-xs text-red-500 ${
                      isNameValid ? "hidden" : ""
                    }`}
                  >
                    Please enter a name.
                  </Label>
                  <Input
                    id="nameInput"
                    className={`mt-2 ${isNameValid ? "" : "border-red-500"}`}
                    placeholder="Joel Vivian"
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                    required
                  />
                </div>
                <div className="space-y-2 flex flex-col">
                  <Label htmlFor="email">Email</Label>
                  <Label
                    htmlFor="email"
                    className={`text-xs text-red-500 ${
                      isEmailValid ? "hidden" : ""
                    }`}
                  >
                    Please enter a valid email address.
                  </Label>
                  <Input
                    id="email"
                    placeholder="joel.vivian@domain.com"
                    className={`${isEmailValid ? "" : "border-red-500"}`}
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                    type="email"
                  />
                </div>
              </div>

              <div className="space-y-2 flex flex-col">
                <Label htmlFor="role">Role</Label>
                <Select
                  disabled={isSetup}
                  required
                  value={`${role}`}
                  onValueChange={(e) => {
                    setRole(Number.parseInt(e));
                  }}
                >
                  <SelectTrigger
                    className={`border border-[#E2E8F0] h-10 rounded-md relative ${
                      isSetup ? "bg-slate-100 text-slate-500" : ""
                    }`}
                  >
                    <SelectValue placeholder="Select a parameter type" />
                    {/* Displays a dropdown arrow. */}
                    <div className="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                      <svg
                        className="h-4 w-4 fill-current text-gray-500"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 20 20"
                      >
                        <path d="M10 12l-6-6-1.414 1.414L10 14.828l7.414-7.414L16 6z"></path>
                      </svg>
                    </div>
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="0">User</SelectItem>
                    <SelectItem value="1">Admin</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {!isSetup && (
                <div className="space-y-2 flex flex-col items-center">
                  <Label htmlFor="randomPass">Generate Random Password?</Label>
                  <Checkbox
                    id="randomPass"
                    checked={generatePass}
                    onCheckedChange={(e) => {
                      const val = e.valueOf();
                      if (typeof val === "boolean") {
                        setGeneratePass(val);
                      }
                    }}
                  />
                </div>
              )}
              {!generatePass && (
                <>
                  <div className="space-y-2 flex flex-col">
                    <Label htmlFor="password">Password</Label>
                    <Label
                      htmlFor="confirmPass"
                      className={`text-xs text-red-500 ${
                        isPasswordValid ? "hidden" : ""
                      }`}
                    >
                      Password must be at least 8 characters, 1 letter, 1
                      number, and 1 special character.
                    </Label>
                    <Input
                      id="password"
                      value={password}
                      className={`${isPasswordValid ? "" : "border-red-500"}`}
                      onChange={(e) => setPassword(e.target.value)}
                      required={!generatePass}
                      type="password"
                    />
                  </div>
                  <div className="space-y-2 flex flex-col">
                    <Label htmlFor="confirmPass">Confirm Password</Label>

                    <Label
                      htmlFor="confirmPass"
                      className={`text-xs text-red-500 ${
                        confirmPassword === password ? "hidden" : ""
                      }`}
                    >
                      Passwords do not match.
                    </Label>

                    <Input
                      id="confirmPass"
                      value={confirmPassword}
                      className={
                        confirmPassword === password ? "" : "border-red-500"
                      }
                      onChange={(e) => setConfirmPassword(e.target.value)}
                      required={!generatePass}
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
              onClick={() => _createAccount()}
            >
              Create User
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  );
};

export default CreateAccount;
