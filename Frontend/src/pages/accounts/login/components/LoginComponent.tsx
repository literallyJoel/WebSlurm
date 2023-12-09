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
import { validateEmail } from "@/helpers/validation";
import { useMutation } from "react-query";
import { LoginObject, login } from "@/helpers/auth";

interface props {
  isExpired: boolean;
}
export default function LoginComponent({ isExpired }: props) {
  const [userEmail, setUserEmail] = useState("");
  const [pass, setPass] = useState("");
  const [isUserEmailValid, setIsUserEmailValid] = useState(true);

  const callLogin = useMutation((loginObject: LoginObject) => {
    return login(loginObject);
  });

  function validateInput(): void {
    let valid = true;

    if (!validateEmail(userEmail)) {
      valid = false;
      setIsUserEmailValid(false);
    }

    if (valid) {
      callLogin.mutate(
        { email: userEmail, password: pass },
        {
          onSuccess: (data) => {
            localStorage.setItem("token", data.token);
          },
        }
      );
    }
  }
  return (
    <Card className="max-w-2xl w-4/12 mx-auto">
      <CardHeader>
        <CardTitle>Login</CardTitle>
        <Label className={`text-xs text-red-500 ${isExpired ? "" : "hidden"}`}>
          Your session has expired. Please login again.
        </Label>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          <div className="space-y-2 flex flex-col">
            <Label htmlFor="email">Email</Label>
            <Label
              htmlFor="email"
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

          <div className="space-y-2 flex flex-col">
            <Label htmlFor="password">Password</Label>
            <Input
              id="password"
              value={pass}
              onChange={(e) => setPass(e.target.value)}
              required={true}
              type="password"
            />
          </div>
        </div>
        <div className="w-full p-4 flex flex-col items-center">
          <Button
            className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text-white hover:bg-uol hover:shadow-inner"
            onClick={() => validateInput()}
          >
            Login
          </Button>
        </div>
      </CardContent>
      <CardFooter className="flex flex-col gap-2">
        <span className="text-slate-400  text-sm w-full">
          <hr className="border border-slate-500 w-full" />
        </span>

        <Button className="w-7/12 bg-uol rounded-lg">Sign In with MWS</Button>
      </CardFooter>
    </Card>
  );
}
