interface MarketingLayoutProps {
  children: React.ReactNode;
}

export default function Layout({ children }: MarketingLayoutProps) {
  return (
    <main
      className="
        min-h-screen
        bg-[url('/background-scrubber.jpg')]
        bg-cover
        bg-center
        bg-no-repeat
      ">
      {children}
    </main>
  );
}
