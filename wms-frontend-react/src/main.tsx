import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import "./index.css";
import "swiper/swiper-bundle.css";
import "flatpickr/dist/flatpickr.css";
import App from "./App";
import { AppWrapper } from "./components/common/PageMeta";
import { ThemeProvider } from "./context/ThemeContext";
import { GoogleOAuthProvider } from '@react-oauth/google'
const clientId = '509022459895-ihr8qkmnuiuii4shu9e6gucrjchgdn5n.apps.googleusercontent.com' // from Google Cloud Console


createRoot(document.getElementById("root")!).render(
  <StrictMode>
    <ThemeProvider>
      <AppWrapper>
        <GoogleOAuthProvider clientId={clientId}>
        <App />
        </GoogleOAuthProvider>
      </AppWrapper>
    </ThemeProvider>
  </StrictMode>,
);
