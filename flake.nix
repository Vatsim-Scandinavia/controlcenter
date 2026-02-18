{
  description = "VATSIM Scandinavia Control Center – Laravel dev environment with PHP 8.3/8.4/8.5 and MySQL";

  # nixos-unstable has php85; use a recent nixpkgs for all three PHP versions
  inputs.nixpkgs.url = "github:NixOS/nixpkgs/nixos-unstable";

  outputs = { self, nixpkgs }: let
    inherit (nixpkgs) lib;
    forAllSystems = lib.genAttrs lib.systems.flakeExposed;
    pkgsFor = system: import nixpkgs {
      inherit system;
      config.allowUnfree = true;
    };

    # Laravel-typical PHP extensions (pdo_mysql, intl, zip, opcache where available)
    phpExts = { enabled, all }: enabled ++ (with all; [
      intl
      zip
      pdo_mysql
    ]) ++ lib.optional (all ? opcache) all.opcache;

    mkPhpEnv = phpBase: phpBase.buildEnv {
      extensions = phpExts;
      extraConfig = ''
        memory_limit = 512M
        date.timezone = UTC
      '';
    };

    mkDevShell = system: let
      pkgs = pkgsFor system;
      php83Env = mkPhpEnv pkgs.php83;
      php84Env = mkPhpEnv pkgs.php84;
      # php85 may require a recent nixpkgs (nixos-unstable); omit if your channel lacks it
      php85Env = if lib.hasAttr "php85" pkgs then mkPhpEnv pkgs.php85 else null;
      php83 = pkgs.writeShellScriptBin "php83" ''exec "${php83Env}/bin/php" "$@"'';
      php84 = pkgs.writeShellScriptBin "php84" ''exec "${php84Env}/bin/php" "$@"'';
      php85 = if php85Env != null then pkgs.writeShellScriptBin "php85" ''exec "${php85Env}/bin/php" "$@"'' else null;
    in pkgs.mkShell {
      name = "controlcenter-dev";
      packages = [
        php84Env   # default `php` and `php-fpm` etc.
        php83
        php84
        pkgs.mariadb   # client (mysql, mysqldump) and server (mysqld) when needed
        pkgs.php84Packages.composer
      ] ++ lib.optional (php85 != null) php85;

      env.PHP_PEAR_SYSCONF_DIR = "/tmp";

      shellHook = ''
        echo ""
        echo "───────────────────────────────────-"
        echo " VATSIM Scandinavia Control Center"
        echo " Development environment"
        echo "────────────────────────────────────"
        echo ""
        echo "  PHP (default)   $(php -v 2>/dev/null | head -1)"
        echo "  php83           $(php83 -v 2>/dev/null | head -1)"
        echo "  php84           $(php84 -v 2>/dev/null | head -1)"
        ${lib.optionalString (php85 != null) ''echo "  php85           $(php85 -v 2>/dev/null | head -1)"''}
        echo "  mysql           $(mysql --version 2>/dev/null || true)"
        echo "  composer        $(composer --version 2>/dev/null || true)"
        echo ""
      '';
    };
  in {
    devShells = forAllSystems (system: {
      default = mkDevShell system;
    });
  };
}
