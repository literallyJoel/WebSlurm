import { Label } from "@/shadui/ui/label";
import { Input } from "@/shadui/ui/input";
import { Button } from "@/shadui/ui/button";
import {
  Card,
  CardHeader,
  CardTitle,
  CardContent,
  CardFooter,
} from "@/shadui/ui/card";
import { useState } from "react";
import { validateEmail, validatePassword } from "@/helpers/validation";
import { useMutation } from "react-query";
import { LoginObject, login } from "@/helpers/auth";

interface props{
    isRequired: boolean;
}

export default function ResetPasswordComponent({isRequired}: props) {
  const [pass, setPass] = useState("");
  const [confirmPass, setConfirmPass] = useState("");
  const [isPassValid, setIsPassValid] = useState(true);
  
  function validateInput(): void {
    if (!validatePassword(pass)) {
      setIsPassValid(false);
    }
  }
  return (
    <Card className="max-w-2xl w-4/12 mx-auto">
      <CardHeader>
        <CardTitle>Reset Password</CardTitle>
        <Label className={`text-xs text-red-500 ${isRequired ? "" : "hidden"}`}>An administrator has required you to reset your password.</Label>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          <div className="space-y-2 flex flex-col">
            <div className="space-y-2 flex flex-col">
              <Label htmlFor="password">Password</Label>
              <Label
                htmlFor="confirmPass"
                className={`text-xs text-red-500 ${
                  isPassValid ? "hidden" : ""
                }`}
              >
                Password must be at least 8 characters, 1 letter, 1 number, and
                1 special character.
              </Label>
              <Input
                id="password"
                value={pass}
                className={`${isPassValid ? "" : "border-red-500"}`}
                onChange={(e) => setPass(e.target.value)}
                required
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
                type="password"
              />
            </div>
          </div>
        </div>
        <div className="w-full p-4 flex flex-col items-center">
          <Button
            className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg-uol hover:shadow-inner"
            onClick={() => validateInput()}
          >
            Reset Password
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}
