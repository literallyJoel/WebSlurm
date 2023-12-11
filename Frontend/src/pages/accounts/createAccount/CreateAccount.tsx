import Nav from "../../components/Nav";
import { useMutation } from "react-query";
import { createAccount, NewAccountObject } from "@/helpers/accountsHelper";
import { CreateAccountView } from "./components/CreateAccountView";
import { CreationFailedView } from "./components/FailureView";
import { CreationSuccessView } from "./components/SuccessView";
import { useEffect, useState } from "react";

const CreateAccountScreen = (): JSX.Element => {
  /*
  Controls what screen should be shown. This can be done directly using the result
  of the backend call, but this makes creating a back button easier.
  */
  const [currentView, setCurrentView] = useState<
    "success" | "failure" | "form"
  >("form");

  //=======================================//
  //============Backend Call==============//
  //=====================================//
  /*
  This defines the backend call for creating a new account,
  using the helpr function in the accountCreation helper. 
  It gets called using .mutate with a newAccount object
  */
  const _createAccount = useMutation((newAccount: NewAccountObject) => {
    //If we want a generated password, it sends a request saying so with no password provided
    if (newAccount.generatedPass) {
      return createAccount(
        newAccount.name,
        newAccount.email,
        newAccount.role,
        newAccount.generatedPass
      );
      //If they provide a password we send that through
    } else {
      return createAccount(
        newAccount.name,
        newAccount.email,
        newAccount.role,
        false,
        newAccount.password!
      );
    }
  });

  //=======================================//
  //===========View Controller============//
  //=====================================//
  /*
  There are three potential views, success, failure, or the form to complete.
  This controls which of these views is shown to the user
  */
  const CurrentView = () => {
    if (currentView === "failure") {
      return <CreationFailedView setView={setCurrentView} />;
    } else if (currentView === "success") {
      return (
        <CreationSuccessView
          generatedPass={_createAccount.data?.generatedPass ?? undefined}
        />
      );
    } else {
      return (
        <CreateAccountView
          createAccount={_createAccount}
          setCurrentView={setCurrentView}
        />
      );
    }
  };

  //=======================================//
  //===============UI Code================//
  //=====================================//
  return (
    <div className="flex flex-col h-screen">
      <Nav />
      <div className="mt-10 flex-grow mb-10">
        <CurrentView />
      </div>
    </div>
  );
};

export default CreateAccountScreen;
