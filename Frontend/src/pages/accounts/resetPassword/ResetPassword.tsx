import Nav from "@/pages/components/Nav";
import ResetPasswordComponent from "./components/ResetPasswordComponent";

interface props{
    isRequired: boolean;
}
export default function ResetPassword({isRequired}: props){
  return (
    <div className="flex flex-col h-screen">
      <Nav />
      <div className="mt-10 w-full flex flex-col items-center">
        <ResetPasswordComponent isRequired={isRequired}/>
      </div>
    </div>
  );
}
