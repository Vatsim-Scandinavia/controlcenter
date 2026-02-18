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
      # Keep version setup compact so adding/removing versions only changes one list.
      desiredPhpVersions = [ "83" "84" "85" ];
      availablePhpVersions = builtins.filter (version: builtins.hasAttr "php${version}" pkgs) desiredPhpVersions;
      defaultPhpVersion = lib.last availablePhpVersions;
      mkPhpVersion = version: let
        phpAttr = "php${version}";
        phpEnv = mkPhpEnv pkgs.${phpAttr};
      in {
        name = "php${version}";
        env = phpEnv;
        wrapper = pkgs.writeShellScriptBin "php${version}" ''exec "${phpEnv}/bin/php" "$@"'';
      };
      phpBins = map mkPhpVersion availablePhpVersions;
      phpByName = builtins.listToAttrs (map (phpBin: {
        name = phpBin.name;
        value = phpBin;
      }) phpBins);
      defaultPhpEnv = phpByName."php${defaultPhpVersion}".env;
      defaultComposer = pkgs.${"php${defaultPhpVersion}Packages"}.composer;
      phpVersionStatusLines = lib.concatStringsSep "\n" (map (version: ''
        echo "  php${version}           $(php${version} -v 2>/dev/null | head -1)"
      '') availablePhpVersions);
    in assert lib.assertMsg (availablePhpVersions != [])
      "No supported PHP versions are available in nixpkgs. Checked: ${lib.concatStringsSep ", " desiredPhpVersions}";
      pkgs.mkShell {
      name = "controlcenter-dev";
      packages = [
        defaultPhpEnv   # default `php` and `php-fpm` etc.
      ] ++ (map (phpBin: phpBin.wrapper) phpBins) ++ [
        pkgs.mariadb   # client (mysql, mysqldump) and server (mysqld) when needed
        defaultComposer
      ];

      env.PHP_PEAR_SYSCONF_DIR = "/tmp";

      shellHook = ''
        echo ""
        echo "────────────────────────────────────"
        echo " VATSIM Scandinavia Control Center"
        echo " Development environment"
        echo "────────────────────────────────────"
        echo ""
        echo "  PHP (default)   $(php -v 2>/dev/null | head -1)"
        ${phpVersionStatusLines}
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
