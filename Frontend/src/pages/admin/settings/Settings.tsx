import Nav from "@/components/Nav";
import { Link, Outlet } from "react-router-dom";

const AdminSettings = (): JSX.Element => {
  return (
    <>
      <Nav />
      <div className="flex h-screen w-full text-center">
        <nav className="w-80 bg-gray-100 dark:bg-gray-800 border-r dark:border-gray-700 h-full px-6 py-4">
          <h1 className="text-xl font-bold mb-4">Admin Settings</h1>
          <ul className="space-y-2">
            <li>
              <Link
                className="flex justify-center py-2 text-lg font-semibold text-gray-700 dark:text-gray-300"
                to="/admin/jobtypes"
              >
                Job Type Management
              </Link>
            </li>
            <li>
              <Link
                className="flex justify-center py-2 text-lg font-semibold text-gray-700 dark:text-gray-300"
                to="/admin/users"
              >
                User Management
              </Link>
            </li>
            {/* <li>
                <a
                  className="flex justify-center py-2 text-lg font-semibold text-gray-700 dark:text-gray-300"
                  href="#"
                >
                  Application Management
                </a>
              </li> */}
            <li>
              <a
                className="flex justify-center py-2 text-lg font-semibold text-gray-700 dark:text-gray-300"
                href="/admin/organisations"
              >
                Organisation Management
              </a>
            </li>
          </ul>
        </nav>
        <main className="flex-grow p-8">
          <Outlet />
        </main>
      </div>
    </>
  );
};

export default AdminSettings;
