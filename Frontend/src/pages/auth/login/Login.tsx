import Nav from "@/pages/components/Nav";
import LoginComponent from "./components/LoginComponent";

interface props {
  isExpired: boolean;
}

export default function Login({ isExpired }: props) {
  return (
    <div className="flex flex-col h-screen">
      <Nav />
      <div className="mt-10 w-full flex flex-col items-center">
        <LoginComponent isExpired={isExpired} />
      </div>
    </div>
  );
}
