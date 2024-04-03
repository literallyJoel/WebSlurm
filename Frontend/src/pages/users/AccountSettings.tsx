import {
  CardTitle,
  CardDescription,
  CardHeader,
  CardContent,
  CardFooter,
  Card,
} from "@/components/shadui/ui/card";
import { Label } from "@/components/shadui/ui/label";
import { Input } from "@/components/shadui/ui/input";
import { Button } from "@/components/shadui/ui/button";
import Nav from "@/components/Nav";
import { useRef, useState } from "react";
import { useAuthContext } from "@/providers/AuthProvider";
import { MdEdit } from "react-icons/md";
import {
  type UpdateAccountRequest,
  updateAccount as updateAccountHelper,
  deleteAccount as deleteAccountHelper,
  validateEmail,
  validateName,
} from "@/helpers/users";
import { disableUserTokens } from "@/helpers/auth";
import { useMutation } from "react-query";
import { refreshToken, verifyPass } from "@/helpers/auth";
import { Link } from "react-router-dom";
import Noty from "noty";
const AccountSettings = (): JSX.Element => {
  const authContext = useAuthContext();
  const token = authContext.getToken();
  const user = authContext.getUser();
  const [isEditingName, setIsEditingName] = useState(false);
  const [name, setName] = useState(user.name);
  const [isNameValid, setIsNameValid] = useState(true);
  const [password, setPassword] = useState("");
  const [isEditingEmail, setIsEditingEmail] = useState(false);
  const [email, setEmail] = useState(user.email);
  const [isEmailValid, setIsEmailValid] = useState(true);
  const [showDeleteModal, setShowDeleteModal] = useState(false);
  const hiddenRef = useRef<HTMLAnchorElement>(null);
  const refreshTokenRequest = useMutation(
    "RefreshToken",
    () => {
      return refreshToken(token);
    },
    {
      onSuccess: (data) => {
        const noty = new Noty({
          type: "success",
          text: "Account updated successfully.",
          timeout: 2000,
        });
        noty.show();
        localStorage.setItem("token", data.token);
        setTimeout(() => {
          window.location.reload();
        }, 2000);
      },
    }
  );

  const updateUserRequest = useMutation(
    "updateUser",
    (updatedUser: UpdateAccountRequest) => {
      return updateAccountHelper(updatedUser, token);
    },
    {
      onSuccess: () => {
        refreshTokenRequest.mutate();
      },
      onError: () =>{
        const noty = new Noty({
          type: "error",
          text: "Failed to update account. Please try again later.",
          timeout: 4000,
        });
        noty.show();
      
      }
    }
  );

  type verfiyPasswordObject = { token: string; password: string };
  const verifyPasswordRequest = useMutation(
    "verifyPassword",
    (verifyPasswordObj: verfiyPasswordObject) => {
      return verifyPass(verifyPasswordObj.password, verifyPasswordObj.token);
    }
  );

  const deleteAccountRequest = useMutation("deleteAccount", (token: string) => {
    return deleteAccountHelper(token);
  });

  const signOutEverywhereRequest = useMutation(
    "signOutEverywhere",
    (token: string) => {
      return disableUserTokens(token);
    },
    {
      onSuccess: () => {
        window.location.reload();
      },
      onError: () =>{
        const noty = new Noty({
          type: "error",
          text: "Operation failed. Please try again later.",
          timeout: 4000,
        });
        noty.show();
      
      
      }
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

    return valid;
  };

  const updateUser = () => {
    if (validateEntry()) {
      const updatedUser: UpdateAccountRequest = {
        name: name,
        email: email,
        role: user.role,
      };
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
            <Link to="/accounts/settings/resetpassword">
              <Button className="ml-auto">Reset Password</Button>
            </Link>
          </div>
        </CardContent>
        <CardFooter>
          <Button className="w-full" onClick={() => updateUser()}>
            Save Changes
          </Button>
        </CardFooter>
        <CardContent className="space-y-4">
          <Button
            className="w-full text-red-500 border-red-500"
            variant="outline"
            onClick={() => setShowDeleteModal(true)}
          >
            Delete Account
          </Button>
          <Button
            onClick={() => signOutEverywhereRequest.mutate(token)}
            className="w-full"
            variant="outline"
          >
            Sign Out Everywhere
          </Button>
        </CardContent>
      </Card>

      {showDeleteModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
          <Card className="w-full max-w-md border-2 border-uol">
            <CardHeader>
              <CardTitle>Confirm Deletion</CardTitle>
              <span className="text-sm text-red-500">
                Completing this action will delete your account. This cannot be
                undone.
              </span>
            </CardHeader>
            <CardContent>
              <Label htmlFor="password">Enter Your Password</Label>
              <Input
                className="mt-2"
                type="password"
                id="password"
                placeholder="Password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </CardContent>
            <CardFooter className="flex flex-row">
              <Link
                to="/accounts/settings"
                className="hidden"
                ref={hiddenRef}
              />
              <Button
                variant="destructive"
                onClick={() => {
                  verifyPasswordRequest.mutate(
                    { token: token, password: password },
                    {
                      onSettled: (isVerified) => {
                        if (isVerified?.ok) {
                          deleteAccountRequest.mutate(token, {
                            onSuccess: () => {
                              hiddenRef.current?.click();
                            },
                          });
                        }
                      },
                    }
                  );
                }}
              >
                Confirm
              </Button>
              <Button
                className="ml-auto"
                variant="outline"
                onClick={() => setShowDeleteModal(false)}
              >
                Cancel
              </Button>
            </CardFooter>
          </Card>
        </div>
      )}

      {showDeleteModal && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
          <Card className="w-full max-w-md border-2 border-uol">
            <CardHeader>
              <CardTitle>Confirm Deletion</CardTitle>
              <span className="text-sm text-red-500">
                Completing this action will delete your account. This cannot be
                undone.
              </span>
            </CardHeader>
            <CardContent>
              <Label htmlFor="password">Enter Your Password</Label>
              <Input
                className="mt-2"
                type="password"
                id="password"
                placeholder="Password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />
            </CardContent>
            <CardFooter className="flex flex-row">
              <Link
                to="/accounts/settings"
                className="hidden"
                ref={hiddenRef}
              />
              <Button
                variant="destructive"
                onClick={() => {
                  verifyPasswordRequest.mutate(
                    { token: token, password: password },
                    {
                      onSettled: (isVerified) => {
                        if (isVerified?.ok) {
                          deleteAccountRequest.mutate(token, {
                            onSuccess: () => {
                              hiddenRef.current?.click();
                            },
                          });
                        }
                      },
                    }
                  );
                }}
              >
                Confirm
              </Button>
              <Button
                className="ml-auto"
                variant="outline"
                onClick={() => setShowDeleteModal(false)}
              >
                Cancel
              </Button>
            </CardFooter>
          </Card>
        </div>
      )}
    </>
  );
};

export default AccountSettings;
