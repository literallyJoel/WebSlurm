import Nav from "@/components/Nav";
import { LoginObject, login } from "@/pages/auth/auth";
import { useState } from "react";
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
import { useMutation } from "react-query";
import { validateEmail } from "@/helpers/validation";
interface props {
  isExpired?: boolean;
}

const Login = ({ isExpired }: props): JSX.Element => {
  const [email, setEmail] = useState("");
  const [pass, setPass] = useState("");
  const [isEmailValid, setIsEmailValid] = useState(true);
  const [isAccountError, setIsAccountError] = useState(false);
  const callLogin = useMutation((loginObject: LoginObject) => {
    return login(loginObject);
  });

  const _login = (): void => {
    if (!validateEmail(email)) {
      setIsEmailValid(false);
    } else {
      callLogin.mutate(
        { email: email, password: pass },
        {
          onSuccess: (data) => {
            localStorage.setItem("token", data.token);
            window.location.pathname === "/auth/login"
              ? (window.location.href = "/")
              : window.location.reload();
          },
          onSettled(data, error, variables, context) {
            console.log("yeet");
            if (error) {
              setIsAccountError(true);
              console.log(error);
            }

            const response = context as { response: { status: number } };
            console.log("ye: ", response.response.status);
          },
        }
      );
    }
  };

  return (
    <div className="flex flex-col h-screen">
      <Nav />
      <div className="mt-10 w-full flex flex-col items-center">
        <Card className="max-w-2xl w-4/12 mx-auto">
          <CardHeader>
            <CardTitle>Login</CardTitle>
            <Label
              className={`text-xs text-red-500 ${isExpired && !isAccountError ? "" : "hidden"}`}
            >
              Your session has expired. Please login again.
            </Label>
            <Label
              className={`text-xs text-red-500 ${
                isAccountError ? "" : "hidden"
              }`}
            >
              Your email or password is incorrect. Please try again.
            </Label>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
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
                className="w-5/12 border-uol border-2 rounded-xl shadow-2xl hover:text=white hover:bg-uol hover:shadow-inner"
                onClick={() => _login()}
              >
                Login
              </Button>
            </div>
          </CardContent>
          <CardFooter className="flex flex-col gap-2">
            <span className="text-slate-400 text-sm w-full">
              <hr className="border border-slate-500 w-full" />
            </span>

            <Button className="w-7/12 bg-uol rounded-lg">
              Sign in with MWS
            </Button>
          </CardFooter>
        </Card>
      </div>
    </div>
  );
};

export default Login;
