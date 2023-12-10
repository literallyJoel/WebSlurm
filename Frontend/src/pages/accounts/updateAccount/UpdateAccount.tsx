import {
  CardTitle,
  CardDescription,
  CardHeader,
  CardContent,
  CardFooter,
  Card,
} from "@/shadui/ui/card";
import { Label } from "@/shadui/ui/label";
import { Input } from "@/shadui/ui/input";
import { Button } from "@/shadui/ui/button";
import Nav from "@/pages/components/Nav";
import { useContext, useState } from "react";
import { AuthContext } from "@/providers/auth/AuthProvider";
import { MdEdit } from "react-icons/md";
import {
  validateEmail,
  validateName,
  validatePassword,
} from "@/helpers/validation";
import {
  UpdateAccountObject,
  updateAccount as updateAccountHelper,
} from "@/helpers/accountsHelper";
import { useMutation } from "react-query";
export default function UpdateAccount() {
  const { getUser } = useContext(AuthContext);
  const user = getUser();
  const [isEditingName, setIsEditingName] = useState(false);
  const [name, setName] = useState(user.name);
  const [isNameValid, setIsNameValid] = useState(true);
  const [isEditingEmail, setIsEditingEmail] = useState(false);
  const [email, setEmail] = useState(user.email);
  const [isEmailValid, setIsEmailValid] = useState(true);
  const [isEditingPassword, setIsEditingPassword] = useState(false);
  const [password, setPassword] = useState("");
  const [confirmPass, setConfirmPass] = useState("");
  const [isPassValid, setIsPassValid] = useState(true);

  const updateUserRequest = useMutation(
    "updateUser",
    (updatedUser: UpdateAccountObject) => {
      return updateAccountHelper(updatedUser);
    }
  );

  const validateEntry = (): boolean => {
    let valid = true;
    if (isEditingName && !validateName(name)) {
      valid = false;
      setIsNameValid(false);
    }

    if (isEditingEmail && !validateEmail(email)) {
      valid = false;
      setIsEmailValid(false);
    }
    if (isEditingPassword && !validatePassword(password)) {
      valid = false;
      setIsPassValid(false);
    }
    if (
      isEditingPassword &&
      !(confirmPass === password) &&
      password !== "" &&
      confirmPass !== ""
    ) {
      valid = false;
    }
    return valid;
  };

  const updateUser = () => {
    if (validateEntry()) {
      const updatedUser: UpdateAccountObject = {};
      if (isEditingName) {
        updatedUser.name = name;
      }

      if (isEditingEmail) {
        updatedUser.email = email;
      }

      if (isEditingPassword && password !== "" && confirmPass !== "") {
        updatedUser.pass = password;
      }

      updateUserRequest.mutate(updatedUser);
    }
  };

  return (
    <>
      <Nav />
      <Card className="w-full max-w-md mx-auto mt-4">
        <CardHeader>
          <CardTitle>Your Account</CardTitle>
          <CardDescription>Manage your account here</CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-2">
            {isEditingName ? (
              <div className="flex flex-col gap-2">
                <Label htmlFor="name">Name</Label>
                <Label
                  htmlFor="confirmPass"
                  className={`text-xs text-red-500 ${
                    isNameValid ? "hidden" : ""
                  }`}
                >
                  Please enter a name.
                </Label>
                <Input
                  id="name"
                  placeholder="name"
                  value={name}
                  onChange={(e) => setName(e.target.value)}
                />
              </div>
            ) : (
              <div className="flex flex-col gap-2">
                <div className="flex flex-row gap-2">
                  <Label htmlFor="name">Name</Label>
                  <MdEdit
                    className="cursor-pointer"
                    onClick={() => setIsEditingName(true)}
                  />
                </div>

                <span id="name">{name}</span>
              </div>
            )}
          </div>
          <div className="space-y-2">
            {isEditingEmail ? (
              <div className="flex flex-col gap-2">
                <Label htmlFor="email">Email</Label>
                <Label
                  htmlFor="confirmPass"
                  className={`text-xs text-red-500 ${
                    isEmailValid ? "hidden" : ""
                  }`}
                >
                  Please enter a valid email address.
                </Label>
                <Input
                  type="email"
                  id="email"
                  placeholder="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                />
              </div>
            ) : (
              <div className="flex flex-col gap-2">
                <div className="flex flex-row gap-2">
                  <Label htmlFor="email">Email</Label>
                  <MdEdit
                    className="cursor-pointer"
                    onClick={() => setIsEditingEmail(true)}
                  />
                </div>

                <span id="email">{email}</span>
              </div>
            )}
          </div>

          <div className="space-y-2">
            {isEditingPassword && (
              <div className="flex flex-col gap-2">
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
                  type="password"
                  id="password"
                  placeholder="Password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                />
                <Label htmlFor="confirmPassword">Confirm Password</Label>
                <Label
                  htmlFor="confirmPass"
                  className={`text-xs text-red-500 ${
                    confirmPass === password ? "hidden" : ""
                  }`}
                >
                  Passwords do not match.
                </Label>
                <Input
                  type="password"
                  id="confirmPassword"
                  className={`${
                    confirmPass === password ? "" : "border-red-500"
                  }`}
                  placeholder="Confirm Password"
                  value={confirmPass}
                  onChange={(e) => setConfirmPass(e.target.value)}
                />
              </div>
            )}
          </div>
          <div className={`${isEditingPassword ? "hidden" : "space-y-2"}`}>
            <Button
              className="ml-auto"
              onClick={() => setIsEditingPassword(true)}
            >
              Update Password
            </Button>
          </div>
        </CardContent>
        <CardFooter>
          <Button className="w-full">Save Changes</Button>
        </CardFooter>
        <CardContent className="space-y-4">
          <Button
            className="w-full text-red-500 border-red-500"
            variant="outline"
          >
            Delete Account
          </Button>
          <Button className="w-full" variant="outline">
            Sign Out Everywhere
          </Button>
        </CardContent>
      </Card>
    </>
  );
}
