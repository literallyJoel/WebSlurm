import { ReactNode, useState, createContext, useContext } from "react";
import { jwtDecode } from "jwt-decode";
import {
  logout as logoutHelper,
  type User,
  type TokenData,
  verifyToken,
} from "@/helpers/auth";
import { useMutation, useQuery } from "react-query";
import Login from "@/pages/auth/Login";
import ResetPassword from "@/pages/auth/ResetPassword";
interface props {
  children: ReactNode;
}

export const AuthContext = createContext({
  getUser: (): User => {
    return { email: "", id: "", name: "", role: -1, isOrgAdmin: false };
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
  const [decoded, setDecoded] = useState<TokenData | null>(
    token ? jwtDecode(token) : null
  );
  //Checks with the backend that the token is valid
  const isTokenValid = useQuery("isTokenValid", () => verifyToken(token ?? ""));
  //Used to log out a user
  const serverLogout = useMutation((_token: string) => {
    return logoutHelper(_token);
  });

  //Used to retreive the user object from other components
  const getUser = (): User => {
    if (decoded) {
      const user = {
        id: decoded.userId,
        name: decoded.name,
        email: decoded.email,
        role: decoded.role,
        isOrgAdmin: decoded.isOrgAdmin,
      };
      return user;
    }

    return { id: "", name: "", email: "", role: -1, isOrgAdmin: false };
  };

  const getToken = (): string => {
    return token ?? "";
  };

  //Logs out the user by disabling the token on the backend and removing it from local storage
  const logout = () => {
    serverLogout.mutate(token ?? "", {
      onSuccess: () => {
        localStorage.removeItem("token");
        setToken(null);
        setDecoded(null);
      },
    });
  };

  if (!token || !decoded) {
    return <Login isExpired={false} />;
  }

  if (decoded && decoded.local) {
    if (isTokenValid.data?.ok !== true) {
      if (isTokenValid.data?.err === 401) {
        console.log("expired");
        return <Login isExpired={true} />;
      }
      console.log("invalid");
      console.log(isTokenValid.data?.err);
      return <Login isExpired={false} />;
    }

    if (decoded.exp < Math.floor(Date.now() / 1000)) {
      return <Login isExpired={true} />;
    }

    if (decoded.requiresPasswordReset === "1") {
      return <ResetPassword isRequired={true} />;
    }
  }

  return (
    <AuthContext.Provider value={{ getUser, getToken, logout }}>
      {children}
    </AuthContext.Provider>
  );
};
export default AuthProvider;
export const useAuthContext = () => useContext(AuthContext);
