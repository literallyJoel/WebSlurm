import { RiAccountCircleFill } from "react-icons/ri";
import { useContext, useState } from "react";
import { AuthContext } from "@/providers/auth/AuthProvider";

const Nav = (): JSX.Element => {
  const { getUser, logout } = useContext(AuthContext);
  const user = getUser();
  const [isDropdownOpen, setIsDropdownOpen] = useState(false);

  const handleDropdownToggle = () => {
    setIsDropdownOpen(!isDropdownOpen);
  };

  const handleLogout = () => {
    logout();
  };

  return (
    <nav className="bg-uol">
      <div className="container mx-auto flex justify-between items-center h-12">
        <div className="text-white font-bold text-xl">WebSlurm</div>
        <div
          className={`${
            user.id === "" && user.id === "" && "hidden"
          } flex space-x-4 relative`}
        >
          <RiAccountCircleFill
            className="text-white hover:text-slate-400 cursor-pointer"
            size={30}
            onClick={handleDropdownToggle}
          />
          {isDropdownOpen && (
            <div className="absolute -right-16 mt-2 top-6 w-40 bg-white p-2 rounded shadow-md">
              <div className="text-center text-uol font-bold">{user.name}</div>

              <div className="text-center cursor-pointer text-blue-500 hover:underline">
                Account Settings
              </div>
              {user.role === 1 && (
                <div className="text-center cursor-pointer text-blue-500 hover:underline">
                  Admin Settings
                </div>
              )}
              <div
                onClick={handleLogout}
                className="text-center cursor-pointer text-blue-500 hover:underline"
              >
                Logout
              </div>
            </div>
          )}
        </div>
      </div>
    </nav>
  );
};

export default Nav;
