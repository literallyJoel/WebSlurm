import { RiAccountCircleFill } from "react-icons/ri";
import { useState } from "react";

import { Link } from "react-router-dom";
import { useAuthContext } from "@/providers/AuthProvider/AuthProvider";

const Nav = (): JSX.Element => {
  const authContext = useAuthContext();
  const { logout, getUser } = authContext;
  const user = getUser();
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);

  return (
    <nav className="bg-uol">
      <div className="container mx-auto flex justify-between items-center h-12">
        <Link to="/" className="text-white font-bold text-xl">
          WebSlurm
        </Link>

        <div
          className={`${
            user.id === "" && user.id === "" && "hidden"
          } flex space-x-4 relative`}
        >
          <RiAccountCircleFill
            className="text-white hover:text-slate-400 cursor-pointer"
            size={30}
            onClick={() => setIsDropdownOpen(!isDropdownOpen)}
          />
          {isDropdownOpen && (
            <div className="absolute -right-16 mt-2 top-6 w-40 bg-white p-2 rounded shadow-md">
              <div className="text-center text-uol font-bold">{user.name}</div>
              <div className="text-center">
                <Link
                  to="/accounts/settings"
                  className="text-uol hover:underline"
                >
                  Account Settings
                </Link>
              </div>
              {Number(user.role) === 1 && (
                <div className="text-center">
                  <Link
                    to="/admin"
                    className="text-uol hover:underline cursor-pointer"
                  >
                    Admin Settings
                  </Link>
                </div>
              )}
              <div className="text-center">
                <div
                  onClick={() => logout()}
                  className="text-uol hover:underline cursor-pointer"
                >
                  Logout
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </nav>
  );
};

export default Nav;
