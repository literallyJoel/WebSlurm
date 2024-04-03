import Nav from "@/components/Nav";
import { useState } from "react";
import { decodeToken, updateAccount, validatePassword } from "@/helpers/users";
import { useMutation } from "react-query";
import Noty from "noty";
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/shadui/ui/card";
import { Label } from "@radix-ui/react-label";
import { Input } from "@/components/shadui/ui/input";
import { Button } from "@/components/shadui/ui/button";
import { useNavigate } from "react-router-dom";

import { refreshToken, verifyPass } from "@/helpers/auth";

interface props {
  isRequired?: boolean;
}

const ResetPassword = ({ isRequired }: props): JSX.Element => {
  //The reset password screen can sometimes be outside of the AuthProvider, so we do some stuff manually
  const token = localStorage.getItem("token");
  const user = token ? decodeToken(token) : undefined;
  const [currentPassword, setCurrentPassword] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [passwordVerified, setPasswordVerified] = useState<boolean>();
  const [isPasswordValid, setIsPasswordValid] = useState(true);
  const navigate = useNavigate();
  const refreshTokenRequest = useMutation(
    "refreshToken",
    () => {
      return refreshToken(token ?? "");
    },
    {
      onSuccess: (data) => {
        localStorage.setItem("token", data.token);
        const noty = new Noty({
          type: "success",
          text: "Password reset successfully. You will be redirected momentarily.",
          timeout: 4000,
        });

        noty.show();
        if (isRequired) {
          setTimeout(() => {
            window.location.reload();
          }, 4000);
        } else
          setTimeout(() => {
            navigate("/accounts/settings");
          }, 5000);
      },
      onError: () => {
        new Noty({
          type: "error",
          text: "An error occurred while updating your password. Please try again.",
          timeout: 5000,
        }).show();
      },
    }
  );
  const updatePasswordRequest = useMutation(
    "updatePassword",
    (password: string) => {
      return updateAccount(
        {
          name: user!.name,
          email: user!.email,
          role: user!.role,
          password: password,
          requiresPasswordReset: false,
        },
        token ?? ""
      );
    },
    {
      onSuccess: () => {
        refreshTokenRequest.mutate();
      },
      onError: () => {
        new Noty({
          type: "error",
          text: "An error occurred while updating your password. Please try again.",
          timeout: 5000,
        }).show();
      },
    }
  );

  const verifyPasswordRequest = useMutation(
    "verifyPassword",
    (password: string) => {
      return verifyPass(password, token ?? "");
    }
  );

  const _updatePassword = (): void => {
    if (!validatePassword(password)) {
      setIsPasswordValid(false);
    } else if (password === confirmPassword) {
      verifyPasswordRequest.mutate(currentPassword, {
        onSuccess: (data) => {
          setPasswordVerified(data.ok);
          if (data.ok) {
            updatePasswordRequest.mutate(password);
          }
        },
      });
    }
  };

  return (
    <div className="flex flex-col h-screen">
      <Nav />

      <div className="mt-10 w-full gap-4 flex flex-col items-center">
        <Card className="max-w-2xl w-4/12 mx-auto">
          <CardHeader>
            <CardTitle>Reset Password</CardTitle>
            <Label
              className={`text-xs text-red-500 ${isRequired ? "" : "hidden"}`}
            >
              An administrator has required that you reset your password.
            </Label>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              <div className="space-y-2 flex flex-col">
                <Label htmlFor="currentPassword">
                  Enter your current password
                </Label>
                <Label
                  htmlFor="password"
                  className={`text-xs text-red-500 ${
                    passwordVerified === true
                      ? "hidden"
                      : passwordVerified === undefined
                      ? "hidden"
                      : ""
                  }`}
                >
                  Incorrect password.
                </Label>
                <Input
                  id="currentPassword"
                  className={`${
                    passwordVerified === true
                      ? ""
                      : passwordVerified === undefined
                      ? ""
                      : "border-red-500"
                  }`}
                  value={currentPassword}
                  onChange={(e) => setCurrentPassword(e.target.value)}
                  type="password"
                  required
                />
                <Label htmlFor="password">New Password</Label>
                <Label
                  htmlFor="password"
                  className={`text-xs text-red-500 ${
                    isPasswordValid ? "hidden" : ""
                  }`}
                >
                  Password must be at least 8 characters long and contain at
                  least one number and one special character.
                </Label>
                <Input
                  className={`${isPasswordValid ? "" : "border-red-500"}`}
                  id="password"
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                />

                <div className="space-y-2 flex flex-col">
                  <Label htmlFor="confirmPass">Confirm New Password</Label>
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
                    className={
                      confirmPassword === password ? "" : "border-red-500"
                    }
                    value={confirmPassword}
                    onChange={(e) => setConfirmPassword(e.target.value)}
                    type="password"
                    required
                  />
                </div>
              </div>
            </div>
            <div className="w-full p-4 flex-col items-center">
              <Button
                className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg=uol hover:shadow-inner"
                onClick={() => _updatePassword()}
              >
                Reset Password
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
};

export default ResetPassword;
