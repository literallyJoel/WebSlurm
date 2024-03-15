import Nav from "@/components/Nav";
import { useMutation } from "react-query";
import { createAccount, type NewAccountObject } from "../accounts";
import { useState } from "react";
import {
  validateEmail,
  validateName,
  validatePassword,
} from "@/helpers/validation";
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/shadui/ui/card";
import { Label } from "@/shadui/ui/label";
import { Input } from "@/shadui/ui/input";
import {
  Select,
  SelectContent,
  SelectItem,
} from "@/shadui/ui/select";
import { Checkbox } from "@/shadui/ui/checkbox";
import { Button } from "@/shadui/ui/button";
import { SelectTrigger, SelectValue } from "@radix-ui/react-select";

const CreateAccount = (): JSX.Element => {
  const [name, setName] = useState("");
  const [email, setEmail] = useState("");
  const [generatePass, setGeneratePass] = useState(false);

  const [role, setRole] = useState(0);
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");

  const [isNameValid, setIsNameValid] = useState(true);
  const [isEmailValid, setIsEmailValid] = useState(true);
  const [isPasswordValid, setIsPasswordValid] = useState(true);

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
            if (data.generatedPass) {
              localStorage.setItem("gpass", data.generatedPass);
              window.location.pathname = "/accounts/create/success";
            }
          },
          onError: (error) => {
            console.log(error);
            window.location.pathname = "/accounts/create/failure";
          },
        }
      );
    }
  };

  const callCreateAccount = useMutation(
    "createAccount",
    (newAccount: NewAccountObject) => {
      if (newAccount.generatedPass) {
        return createAccount(
          newAccount.name,
          newAccount.email,
          newAccount.role,
          newAccount.generatedPass
        );
      } else {
        return createAccount(
          newAccount.name,
          newAccount.email,
          newAccount.role,
          false,
          newAccount.password!
        );
      }
    }
  );

  return (
    <div className="flex flex-col h-screen">
      <Nav />
      <div className="mt-10 flex-grow mb-10">
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
                  required
                  value={`${role}`}
                  onValueChange={(e) => {
                    setRole(Number.parseInt(e));
                  }}
                >
                  <SelectTrigger className="border border-[#E2E8F0] h-10 rounded-md relative">
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
