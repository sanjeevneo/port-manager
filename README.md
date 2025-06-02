# Port Manager

**Port Manager** is a dead simple tool that helps developers find free ports for their Docker containers without the hassle. It scans your system and Docker setup to suggest a port that’s good to go, all within a specified range.

## Features

- Checks Docker and system ports to avoid conflicts.
- Picks a port between the range specified in the `conf.ini` file.
- **Verifications**:
  - Socket test: `Is it free?`
  - Docker check: `No container using it?`
  - System check: `No service hogging it?`
  - Range check: `Is it within the safe range?`
- **Full Info**: Detailed breakdown of containers and ports.

## Usage

Port Manager offers two ways: a web interface or a command-line tool. *Pick your flavor*

### Web Interface (PHP)

1. Clone the repository:
   ```bash
   git clone https://github.com/sanjeevneo/port-manager.git
   cd port-manager
   ```
2. Ensure PHP is installed and configured with your web server (e.g., Apache or Nginx).
3. Update `conf.ini` with your desired port range and settings.
4. **For Synology NAS**: Add the `http` user to the `docker` group so the web server can talk to Docker without permission headaches:
   ```bash
   sudo synogroup --add docker http
   ```
5. Point your browser to `http://localhost/port-manager/index.php` and you’re set!

### Command-Line Tool (Python)

If hosting a web interface isn’t your thing, grab the standalone Python script:

1. Download `docker-run-with-port.py` from the [repository](https://github.com/sanjeevneo/port-manager).
2. Make it executable:
   ```bash
   chmod +x docker-run-with-port.py
   ```
3. Run it:
   - For just the port number:
     ```bash
     ./docker-run-with-port.py -p
     ```
   - For a full rundown of ports and containers:
     ```bash
     ./docker-run-with-port.py
     ```

## License

This project is licensed under the MIT License. You’re free to use, tweak, or share it, but keep the attribution intact:

> Copyright (c) [sanjeevneo/port-manager]. Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions: The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

## Contributing

Got ideas or want to help out? We’d love to have you. For bugs or feature requests, just pop an issue on [GitHub](https://github.com/sanjeevneo/port-manager).
