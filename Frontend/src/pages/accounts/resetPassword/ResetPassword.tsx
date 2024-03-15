import Nav from "@/components/Nav";
import { useState } from "react";
import { updateAccount } from "@/helpers/accounts";
import { useMutation } from "react-query";
import { validatePassword } from "@/helpers/validation";
import { Card, CardContent, CardHeader, CardTitle } from "@/shadui/ui/card";
import { Label } from "@radix-ui/react-label";
import { Input } from "@/shadui/ui/input";
import { Button } from "@/shadui/ui/button";

interface props {
  isRequired?: boolean;
}

const ResetPassword = ({ isRequired }: props): JSX.Element => {
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [isPasswordValid, setIsPasswordValid] = useState(true);
  const updatePasswordRequest = useMutation(
    "updatePassword",
    (password: string) => {
      return updateAccount({ password: password });
    }
  );

  const _updatePassword = (): void => {
    if (!validatePassword(password)) {
      setIsPasswordValid(false);
    } else {
      updatePasswordRequest.mutate(password, {
        onSuccess: () => {
          window.location.href = "/";
        },
      });
    }
  };

  return (
    <div className="flex flex-col h-screen">
      <Nav />
      <div className="mt-10 w-full flex flex-col items-center">
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
                <Label htmlFor="password">Password</Label>
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
