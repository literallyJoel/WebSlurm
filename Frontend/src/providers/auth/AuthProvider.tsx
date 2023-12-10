import { ReactNode, useState, createContext } from "react";
import { jwtDecode } from "jwt-decode";
import { logout as logoutHelper, type User } from "@/helpers/auth";
import Login from "@/pages/auth/login/Login";
import { decodedToken, verifyToken } from "@/helpers/authProvider";
import { useMutation, useQuery } from "react-query";
import ResetPassword from "@/pages/accounts/resetPassword/ResetPassword";
interface props {
  children: ReactNode;
}

export const AuthContext = createContext({
  getUser: (): User => {
    return { email: "", id: "", name: "", role: -1 };
  },
  getToken: (): string => {
    return "";
  },
  logout: () => {},
});
const AuthProvider = ({ children }: props): JSX.Element => {
  //Grabs the token from local storage
  const [token, setToken] = useState<string | null>(
    localStorage.getItem("token")
  );

  //Decodes the token
  const [decoded, setDecoded] = useState<decodedToken | null>(
    token ? jwtDecode(token) : null
  );

  //Checks with the backend if the token is valid
  const isTokenValid = useQuery("tokenValidation", () =>
    verifyToken(token ?? "")
  );

  const serverLogout = useMutation((_token: string) => {
    return logoutHelper(_token);
  });

  //Used to retrieve the user object from other components
  const getUser = (): User => {
    if (decoded) {
      const user = {
        id: decoded.userID,
        name: decoded.name,
        email: decoded.email,
        role: decoded.privLevel,
      };
      return user;
    }

    return { id: "", name: "", email: "", role: -1 };
  };

  //Used to retrieve the token from other components
  const getToken = (): string => {
    if (token) {
      return token;
    } else {
      return "";
    }
  };

  //Logsout the user by disabling the token on the backend and removing the token from local stroage
  const logout = () => {
    serverLogout.mutate(token ?? "", {
      onSuccess: () => {
        localStorage.removeItem("token");
        setToken(null);
        setDecoded(null);
      },
    });
  };
  //Return login screen if there is no token
  if (!token) {
    console.log("No token");
    return <Login isExpired={false} />;
  }

  //Not yet fully implmented, in theory will be used to differentiate between local and LDAP accounts
  if (decoded && decoded.local) {
    //Checks if the token is valid using the isTokenValid query from above
    if (isTokenValid.data !== true) {
      console.log("token is invalid");
      //If the token is invalid, return the login screen.
      return (
        <>
          <Login isExpired={false} />
        </>
      );
    }

    const now = Math.floor(Date.now() / 1000);
    //Checks if the token is expired
    if (decoded.exp < now) {
      console.log("Token is exipred");
      return <Login isExpired={true} />;
    }

    //Checks if the uesr must reset their password
    if (decoded.requiresPasswordReset) {
      console.log("Requires Reset");
      return <ResetPassword isRequired={true} />;
    }
  }

  //There's no situation where this should be the case, but better to cover edge cases.
  if (!decoded) {
    console.log("No decoded");
    return <Login isExpired={false} />;
  }

  return (
    <AuthContext.Provider value={{ getUser, getToken, logout }}>
      {children}
    </AuthContext.Provider>
  );
};

export default AuthProvider;
