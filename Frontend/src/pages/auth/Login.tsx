import Nav from "@/components/Nav";
import { LoginRequest, login } from "@/helpers/auth";
import { useRef, useState } from "react";
import { Label } from "@/components/shadui/ui/label";
import { Input } from "@/components/shadui/ui/input";
import { Button } from "@/components/shadui/ui/button";
import {
  Card,
  CardHeader,
  CardTitle,
  CardContent,
  CardFooter,
} from "@/components/shadui/ui/card";
import { useMutation, useQuery } from "react-query";
import { validateEmail, getShouldSetup } from "@/helpers/users";
import CreateAccount from "@/pages/users/create/CreateAccount";
import { Link } from "react-router-dom";

interface props {
  isExpired?: boolean;
}

const Login = ({ isExpired }: props): JSX.Element => {
  const [email, setEmail] = useState("");
  const [pass, setPass] = useState("");
  const [isEmailValid, setIsEmailValid] = useState(true);
  const [isAccountError, setIsAccountError] = useState(false);
  const [shouldSetup, setShouldSetup] = useState<boolean>();
  const homeRef = useRef<HTMLAnchorElement>(null);
  const authRef = useRef<HTMLAnchorElement>(null);
  useQuery(
    "shouldSetup",
    () => {
      return getShouldSetup();
    },
    {
      onSuccess: (data) => {
        setShouldSetup(data.shouldSetup);
      },
    }
  );
  const callLogin = useMutation((loginObject: LoginRequest) => {
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
              ? (window.location.pathname = "/")
              : window.location.reload();
          },
          onSettled(_, error) {
            if (error) {
              setIsAccountError(true);
              console.log(error);
            }
          },
        }
      );
    }
  };

  if (shouldSetup === undefined) {
    return (
      <div className="flex flex-col h-screen">
        <Nav />
        <Link to="/" className="hidden" ref={homeRef} />
        <Link to="/accounts/create" className="hidden" ref={authRef} />
        <div className="mt-10 w-full flex flex-col h-full gap-2 items-center justify-center">
          <div role="status">
            <svg
              aria-hidden="true"
              className="w-24 h-24 text-gray-200 animate-spin dark:text-gray-600 fill-uol"
              viewBox="0 0 100 101"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z"
                fill="currentColor"
              />
              <path
                d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z"
                fill="currentFill"
              />
            </svg>
            <span className="sr-only">Loading...</span>
          </div>
        </div>
      </div>
    );
  } else if (shouldSetup) {
    return <CreateAccount isSetup={true} />;
  } else {
    return (
      <div className="flex flex-col h-screen">
        <Nav />
        <div className="mt-10 w-full flex flex-col items-center">
          <Card className="max-w-2xl w-4/12 mx-auto">
            <CardHeader>
              <CardTitle>Login</CardTitle>
              <Label
                className={`text-xs text-red-500 ${
                  isExpired && !isAccountError ? "" : "hidden"
                }`}
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

              <Button
                disabled
                className="w-7/12 bg-uol rounded-lg flex flex-col"
              >
                <div>Sign in with MWS</div>
                <div>(coming soon...)</div>
              </Button>
            </CardFooter>
          </Card>
        </div>
      </div>
    );
  }
};

export default Login;
